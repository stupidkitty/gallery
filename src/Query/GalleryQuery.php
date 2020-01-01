<?php
namespace SK\GalleryModule\Query;

use yii\db\Expression;
use yii\db\ActiveQuery;
use SK\GalleryModule\Model\GalleryInterface;

class GalleryQuery extends ActiveQuery
{
    public function asThumbs()
    {
        return $this->select(['gallery_id', 'image_id', 'slug', 'title', 'orientation', 'likes', 'dislikes', 'images_num', 'comments_num', 'views', 'template', 'published_at'])
            ->with(['categories' => function ($query) {
                $query->select(['category_id', 'title', 'slug'])
                    ->where(['enabled' => 1]);
            }])
            ->with(['coverImage' => function ($query) {
                $query->select(['image_id', 'gallery_id', 'path']);
            }]);
    }

    public function onlyActive()
    {
        return $this->andWhere(['status' => GalleryInterface::STATUS_ACTIVE]);
    }

    public function untilNow()
    {
        return $this->andWhere(['<=', 'published_at', new Expression('NOW()')]);
    }

    public function rangedUntilNow($rangeStart)
    {
        $timeagoExpression = $this->getTimeagoExpression($rangeStart);

        return $this->andWhere(['between', 'published_at', new Expression($timeagoExpression), new Expression('NOW()')]);
    }

    /**
     * Кеширует подсчет элементов датасета. Кастыль :(
     */
    public function cachedCount(): int
    {
        $count = $this
            ->cache(300)
            ->count();

        $this->noCache();

        return $count;
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
}
