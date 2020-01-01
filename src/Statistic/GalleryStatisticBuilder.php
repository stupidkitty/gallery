<?php
namespace SK\GalleryModule\Statistic;

use Yii;
use yii\db\Expression;
use SK\GalleryModule\Model\Gallery;
use SK\GalleryModule\Model\Category;
use SK\GalleryModule\Model\Image;
use SK\GalleryModule\Statistic\Report\GalleryStatisticReport;

class GalleryStatisticBuilder
{

    public function build()
    {
        $report = new GalleryStatisticReport();

        $report->setTotalGalleries($this->countTotalGalleries());
        $report->setDisabledGalleries($this->countDisabledGalleries());
        $report->setActiveGalleries($this->countActiveGalleries());
        $report->setModerateGalleries($this->countModerateGalleries());
        $report->setDeletedGalleries($this->countDeletedGalleries());
        $report->setAutopostingGalleries($this->countAutopostingGalleries());

        $report->setTotalCategories($this->countTotalCategories());
        $report->setEnabledCategories($this->countEnabledCategories());
        $report->setDisabledCategories($this->countDisabledCategories());
        $report->setTotalImages($this->countTotalImages());

        return $report;
    }

    /**
     * Подсчитывает все видео в базе.
     *
     * @return integer
     */
    protected function countTotalGalleries(): int
    {
        $num = Gallery::find()
            ->count();

        return $num;
    }

    /**
     * Подсчитывает видео со статусом "disabled".
     *
     * @return integer
     */
    protected function countDisabledGalleries(): int
    {
        $num = Gallery::find()
            ->where(['status' => Gallery::STATUS_DISABLED])
            ->count();

        return $num;
    }

    /**
     * Подсчитывает видео со статусом "active".
     *
     * @return integer
     */
    protected function countActiveGalleries(): int
    {
        $num = Gallery::find()
            ->where(['status' => Gallery::STATUS_ACTIVE])
            ->count();

        return $num;
    }

    /**
     * Подсчитывает видео со статусом "moderation".
     *
     * @return integer
     */
    protected function countModerateGalleries(): int
    {
        $num = Gallery::find()
            ->where(['status' => Gallery::STATUS_MODERATE])
            ->count();

        return $num;
    }

    /**
     * Подсчитывает видео со статусом "delete".
     *
     * @return integer
     */
    protected function countDeletedGalleries(): int
    {
        $num = Gallery::find()
            ->where(['status' => Gallery::STATUS_DELETED])
            ->count();

        return $num;
    }

    /**
     * Подсчитывает сколько видео находится в автопостинге.
     *
     * @return integer
     */
    protected function countAutopostingGalleries(): int
    {
        $num = Gallery::find()
            ->where(['>=', 'published_at', new Expression('NOW()')])
            ->onlyActive()
            ->count();

        return $num;
    }

    /**
     * Подсчитывает все категории.
     *
     * @return integer
     */
    protected function countTotalCategories(): int
    {
        $num = Category::find()
            ->count();

        return $num;
    }

    /**
     * Подсчитывает активные категории.
     *
     * @return integer
     */
    protected function countEnabledCategories(): int
    {
        $num = Category::find()
            ->where(['enabled' => 1])
            ->count();

        return $num;
    }

    /**
     * Подсчитывает отключенные категории.
     *
     * @return integer
     */
    protected function countDisabledCategories(): int
    {
        $num = Category::find()
            ->where(['enabled' => 0])
            ->count();

        return $num;
    }

    /**
     * Подсчитывает все изображения.
     *
     * @return integer
     */
    protected function countTotalImages(): int
    {
        $num = Image::find()
            ->count();

        return $num;
    }
}
