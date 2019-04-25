<?php
namespace SK\GalleryModule\Widget;

use Yii;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\base\Widget;
use yii\base\InvalidConfigException;
use \yii\db\Expression;

use SK\GalleryModule\Model\Gallery;
use SK\GalleryModule\Model\RotationStats;
use SK\GalleryModule\Model\GalleriesCategoriesMap;

class BestGalleries extends Widget
{
    private $cacheKey = 'gallery:widget:bestgalleries';

    /**
     * @var int Идентификатор текущей активной категории;
     */
    public $active_id = null;

    /**
     * @var string path to template
     */
    public $template;

    /**
     * @var array|string сортировка элементов
     * Можно использовать следующие параметры:
     * - category_id: integer, идентификатор категории
     * - title: string, название
     * - position: integer, порядковый номер при ручной сортировке
     * - ctr: float, рассчитаный цтр по кликабельности категории.
     */
    public $order = 'ctr';

    /**
     * @var int Сколько роликов выводить
     */
    public $limit = 20;

    /**
     * @var string Ограничение по времени
     */
    public $timeAgoLimit = 'all-time';

    /**
     * @var int Время жизни кеша темплейта (html)
     */
    public $cacheDuration = 300;

    /**
     * Initializes the widget
     */
    public function init() {
        parent::init();

        if (!in_array($this->order, ['views', 'likes', 'ctr'])) {
            $this->order = 'ctr';
        }

        if (!in_array($this->timeAgoLimit, ['daily', 'weekly', 'monthly', 'yearly', 'all-time'])) {
            $this->timeAgoLimit = 'all-time';
        }
    }

    public function getViewPath()
    {
        return Yii::getAlias('@root/views/gallery');
    }

    /**
     * Runs the widget
     *
     * @return string|void
     */
    public function run() {
        $cacheKey = $this->buildCacheKey();

        $html = Yii::$app->cache->get($cacheKey);

        if (false === $html) {
            $galleries = $this->getGalleries();

            if (empty($galleries)) {
                return;
            }

            $html = $this->render($this->template, [
                'galleries' => $galleries,
            ]);

            Yii::$app->cache->set($cacheKey, $html, $this->cacheDuration);
        }

        return $html;
    }

    private function getGalleries()
    {
        $query = Gallery::find()
            ->asThumbs();

        if ('all-time' === $this->timeAgoLimit) {
            $query->untilNow();
        } else {
            $query->rangedUntilNow($this->timeAgoLimit);
        }

        $query->onlyActive();

        if ('ctr' === $this->order) {
            $query->orderBy(['max_ctr' => SORT_DESC]);
        } else {
            $query->orderBy([$this->order => SORT_DESC]);
        }

        $result = $query
            ->limit($this->limit)
            ->all();

        if (count($result) < $this->limit) {
            $query->where(['and', ['<=', 'published_at', new Expression('NOW()')], ['status' => Gallery::STATUS_ACTIVE]]);

            $result = $query->all();
        }

        return $result;
    }

    private function buildCacheKey()
    {
        return "{$this->cacheKey}:{$this->order}:{$this->timeAgoLimit}:{$this->template}";
    }
}
