<?php
namespace SK\GalleryModule\Provider;

use Yii;

use yii\data\BaseDataProvider;
use yii\db\Expression;
use yii\db\QueryInterface;
use yii\db\ActiveQueryInterface;

use SK\GalleryModule\Model\Gallery;
use SK\GalleryModule\Model\GalleriesCategoriesMap;
use SK\GalleryModule\Model\RotationStats;

class RotateGalleryProvider extends BaseDataProvider
{
    /**
     * @var QueryInterface the query that is used to fetch data models and [[totalCount]]
     * if it is not explicitly set.
     */
    public $query;
    /**
     * @var string|callable the column that is used as the key of the data models.
     * This can be either a column name, or a callable that returns the key value of a given data model.
     *
     * If this is not set, the following rules will be used to determine the keys of the data models:
     *
     * - If [[query]] is an [[\yii\db\ActiveQuery]] instance, the primary keys of [[\yii\db\ActiveQuery::modelClass]] will be used.
     * - Otherwise, the keys of the [[models]] array will be used.
     *
     * @see getKeys()
     */
    public $key;
    /**
     * Initializes the DB connection component.
     * This method will initialize the [[db]] property to make sure it refers to a valid DB connection.
     * @throws InvalidConfigException if [[db]] is invalid.
     */
    public $testPerPagePercent = 15;
    public $testGalleriesStartPosition = 4;
    public $category_id;

    public $datetimeLimit = 'all-time';

    private $cache;

    /**
     *
     * SELECT `g`.*
     * FROM `galleries` as `g`
     * INNER JOIN `galleries_stats` AS `vs` ON (`g`.`gallery_id` = `gs`.`gallery_id` AND `g`.`image_id` = `gs`.`image_id`)
     * WHERE `g`.`published_at` <= NOW() AND `g`.`status` = 10 AND `gs`.`category_id` = 9 AND `gs`.`is_tested` = 1
     * ORDER BY `gs`.`ctr` DESC
     */
	public function init()
    {
        parent::init();

        $this->query = Gallery::find()
            ->alias('g')
            ->select(['g.gallery_id', 'g.image_id', 'g.slug', 'g.title', 'g.orientation', 'g.likes', 'g.dislikes', 'g.images_num', 'g.comments_num', 'g.views', 'g.published_at', 'gs.is_tested', 'gs.ctr'])
            ->innerJoin(['gs' => RotationStats::tableName()], 'g.gallery_id = gs.gallery_id AND g.image_id = gs.image_id')
            ->with(['categories' => function ($query) {
                $query->select(['category_id', 'title', 'slug', 'h1'])
                    ->where(['enabled' => 1]);
            }])
            ->with(['coverImage' => function ($query) {
                $query->select(['image_id', 'gallery_id', 'path', 'source_url'])
                    ->where(['enabled' => 1]);
            }]);

        if ('all-time' === $this->datetimeLimit) {
            $this->query->andWhere(['<=', 'g.published_at', new Expression('NOW()')]);
        } elseif ($this->isValidRange($this->datetimeLimit)) {
            $timeagoExpression = $this->getTimeagoExpression($this->datetimeLimit);

            $this->query->andWhere(['between', 'g.published_at', new Expression($timeagoExpression), new Expression('NOW()')]);
        }

        $this->query
            ->andWhere(['g.status' => Gallery::STATUS_ACTIVE])
            ->orderBy(['ctr' => SORT_DESC])
            ->indexBy('gallery_id');

		$this->cache = Yii::$app->cache;
    }


    /**
     * @inheritdoc
     */
    protected function prepareModels()
    {
        $query = clone $this->query;
        $testPerPagePercent = (int) $this->testPerPagePercent;

        $totalCount = $this->getTotalCount();

        // если видосов в категории
        if (0 === $totalCount) {
            return [];
        }

        $pagination = $this->getPagination();

        if (false !== $pagination) {
            $pagination->totalCount = $totalCount;

            /** @var integer текущая страница */
            $page = $pagination->getPage();
            $perPage = $pagination->getPageSize();
            /** @var integer всего страниц */
			$totalPagesNum = $pagination->getPageCount();
        } else {
			$perPage = 20;
			$page = 0;
        	$totalPagesNum = ceil($totalCount / $perPage);
        }

        /** @var integer сколько завершивших тест всего */
        $totalTestedCount = $this->getTestedCount();

		// если прошедших тест нет, выводим все по порядку.
		if (0 === $totalTestedCount) {
			return $query
               ->andWhere(['gs.category_id' => $this->category_id])
               ->andWhere(['gs.is_tested' => 0])
               ->offset($pagination->getOffset())
               ->limit($pagination->getLimit())
               ->all();
		}

        /** @var integer сколько тестовых всего */
        $totalTestCount = $totalCount - $totalTestedCount;

		// если все прошли тест, выводим все по порядку.
		if (0 === $totalTestCount) {
			return $query
                ->andWhere(['gs.category_id' => $this->category_id])
                ->andWhere(['gs.is_tested' => 1])
               ->offset($pagination->getOffset())
               ->limit($pagination->getLimit())
               ->all();
		}

       /** @var integer сколько тестовых на одну страницу по умолчанию */
		$testPerPage = ceil($perPage * $testPerPagePercent / 100);

		/** @var integer сколько завершивших тест на одну страницу по умолчанию */
		$testedPerPage = $perPage - $testPerPage;

		/** @var integer сколько страниц получилось завешивших тест */
		$testedPagesNum = ceil($totalTestedCount / $testedPerPage);

		/** @var integer сколько страниц получилось тестируемых тумб (нужна ли) */
		$testPagesNum = ceil($totalTestCount / $testPerPage);

		/** @var integer пограничная зона закончившихся тумб */
		$boundaryPage = (int) min($testedPagesNum, $testPagesNum);

		if (($page + 1) < $boundaryPage) { // считаем по процентам
			$offsetTested = $page * $testedPerPage;
			$limitTested = $testedPerPage;

			$offsetTest = $page * $testPerPage;
			$limitTest = $testPerPage;
		} elseif (($page + 1) === $boundaryPage) {
			if ($testedPagesNum == $boundaryPage) {

				$remainderTested = $totalTestedCount - ($page * $testedPerPage);
				$offsetTested = $totalTestedCount - $remainderTested;
				$limitTested = $remainderTested;

				$offsetTest = $page * $testPerPage;
				$limitTest = $perPage - $remainderTested;
			} else {
				$remainderTest = $totalTestCount - ($page * $testPerPage);
				$offsetTest = $totalTestCount - $remainderTest;
				$limitTest = $remainderTest;

				$offsetTested = $page * $testedPerPage;
				$limitTested = $perPage - $remainderTest;
			}
		} else {
			if ($testedPagesNum == $boundaryPage) {
				$remainderTested = $totalTestedCount - (($boundaryPage - 1) * $testedPerPage);
				$offsetTested = 0;
				$limitTested = 0;

				$offsetTest = ($boundaryPage - 1) * $testPerPage + ($perPage - $remainderTested);
				$offsetTest += ($page - $boundaryPage) * $perPage;
				$limitTest = $perPage;
			} else {
				$remainderTest = $totalTestCount - (($boundaryPage - 1) * $testPerPage);
				$offsetTest = 0;
				$limitTest = 0;

				$offsetTested = ($boundaryPage - 1) * $testedPerPage + ($perPage - $remainderTest);
				$offsetTested += ($page - $boundaryPage) * $perPage;
				$limitTested = $perPage;
			}
		}

		$testQuery = clone $query;

		$testedModels = $query
            ->andWhere(['gs.category_id' => $this->category_id])
            ->andWhere(['gs.is_tested' => 1])
            ->offset((int) $offsetTested)
            ->limit((int) $limitTested)
            ->all();

		$testModels = $testQuery
            ->andWhere(['gs.category_id' => $this->category_id])
            ->andWhere(['gs.is_tested' => 0])
            ->offset((int) $offsetTest)
            ->limit((int) $limitTest)
            ->all();

		// Перемешаем тестовые тумбы.
		$resultArray = $testedModels + $testModels;

		if (($page + 1) <= $boundaryPage && count($resultArray) > $this->testGalleriesStartPosition) {
			$firstGalleries = array_splice($resultArray, 0, $this->testGalleriesStartPosition);
			shuffle($resultArray);

			return array_merge($firstGalleries, $resultArray);
		}

        return $resultArray;
    }

    /**
     * @inheritdoc
     */
    protected function prepareKeys($models)
    {
        $keys = [];

        if ($this->key !== null) {
            foreach ($models as $model) {
                if (is_string($this->key)) {
                    $keys[] = $model[$this->key];
                } else {
                    $keys[] = call_user_func($this->key, $model);
                }
            }

            return $keys;
        } elseif ($this->query instanceof ActiveQueryInterface) {
            /* @var $class \yii\db\ActiveRecordInterface */
            $class = $this->query->modelClass;
            $pks = $class::primaryKey();

            if (count($pks) === 1) {
                $pk = $pks[0];

                foreach ($models as $model) {
                    $keys[] = $model[$pk];
                }
            } else {
                foreach ($models as $model) {
                    $kk = [];

                    foreach ($pks as $pk) {
                        $kk[$pk] = $model[$pk];
                    }

                    $keys[] = $kk;
                }
            }

            return $keys;
        }

        return array_keys($models);
    }

    /**
     * Подсчитывает количество активных видео в выбранной категории
     *
     * @return integer
     */
    protected function prepareTotalCount(): int
    {
        $cacheKey = "rotate:gallery:provider:totalcount:{$this->category_id}";

        $count = $this->cache->get($cacheKey);

        if (false === $count) {
            $count = Gallery::find()
                ->alias('g')
                ->innerJoin(['gcm' => GalleriesCategoriesMap::tableName()], 'g.gallery_id = gcm.gallery_id')
                ->andWhere(['<=', 'g.published_at', new Expression('NOW()')])
                ->andWhere(['g.status' => Gallery::STATUS_ACTIVE])
                ->andWhere(['gcm.category_id' => $this->category_id])
                ->count();

            $this->cache->set($cacheKey, $count, 300);
        }

        return $count;
    }

    /**
     * Подсчитывает количество активных видео прошедших тестирование
     *
     * @return integer
     */
    protected function getTestedCount(): int
    {
        return Gallery::find()
        	->alias('g')
            ->innerJoin(['gs' => RotationStats::tableName()], 'g.gallery_id = gs.gallery_id AND g.image_id = gs.image_id')
            ->andWhere(['<=', 'g.published_at', new Expression('NOW()')])
            ->andWhere(['g.status' => Gallery::STATUS_ACTIVE])
            ->andWhere(['gs.category_id' => $this->category_id])
            ->andWhere(['gs.is_tested' => 1])
            ->count();
    }

    /**
     * Возвращает выражение для первого значения в выборке по интервалу времени.
     * Значения: daily, weekly, monthly, early, all_time
     *
     * @param string $time Ограничение по времени.
     *
     * @return string.
     *
     * @throws NotFoundHttpException
     */
    protected function getTimeagoExpression($time): string
    {
        $times = [
            'daily' => '(NOW() - INTERVAL 1 DAY)',
            'weekly' => '(NOW() - INTERVAL 1 WEEK)',
            'monthly' => '(NOW() - INTERVAL 1 MONTH)',
            'yearly' => '(NOW() - INTERVAL 1 YEAR)',
        ];

        if (isset($times[$time])) {
            return $times[$time];
        }

        return $times['yearly'];
    }

    /**
     * Проверяет корректность параметра $t в экшене контроллера.
     * Значения: daily, weekly, monthly, early, all_time
     *
     * @param string $time Ограничение по времени.
     *
     * @return boolean
     */
    protected function isValidRange($time): bool
    {
        return in_array($time, ['daily', 'weekly', 'monthly', 'yearly', 'all-time']);
    }
}
