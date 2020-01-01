<?php
namespace SK\GalleryModule\Statistic;

use Yii;
use yii\db\Expression;
use SK\GalleryModule\Model\Gallery;
use SK\GalleryModule\Model\Category;
use SK\GalleryModule\Model\RotationStats;
use SK\GalleryModule\Statistic\Report\CategoryRotationReport;
use SK\GalleryModule\Statistic\Report\RotationStatisticReport;

class RotationStatisticBuilder
{

    public function build()
    {
        $report = new RotationStatisticReport();

        $report->setTotalThumbs($this->calculateTotalThumbs());
        $report->setTestThumbs($this->calculateTestThumbs());
        $report->setTestedThumbs($this->calculateTestedThumbs());
        $report->setTestedZeroCtrThumbs($this->calculateTestedZeroCtrThumbs());

        $report->setCategoriesReports($this->buildCategoriesReport());

        /*dump(
            $this->calculateCategoriesUntilNowThumbs(),
            $this->calculateCategoriesAutopostingThumbs()
        );exit();*/

        return $report;
    }

    public function buildCategoriesReport()
    {
        $categoriesReports = [];

        $categoriesTotalThumbs = $this->calculateCategoriesTotalThumbs();
        $categoriesTestThumbs = $this->calculateCategoriesTestThumbs();
        $categoriesTestedThumbs = $this->calculateCategoriesTestedThumbs();
        $categoriesAutopostingThumbs = $this->calculateCategoriesAutopostingThumbs();

        $categories = Category::find()
            ->select(['category_id', 'title', 'slug'])
            ->where(['enabled' => 1])
            ->indexBy('category_id')
            ->all();

        foreach ($categories as $key => $category) {
            $report = new CategoryRotationReport();

            $report->setId($category->getId());
            $report->setTitle($category->getTitle());
            $report->setSlug($category->getSlug());

            $categoryTotalThumbs = isset($categoriesTotalThumbs[$key]) ? (int) $categoriesTotalThumbs[$key] : 0;
            $report->setTotalThumbs($categoryTotalThumbs);

            $categoryTestThumbs = isset($categoriesTestThumbs[$key]) ? (int) $categoriesTestThumbs[$key] : 0;
            $report->setTestThumbs($categoryTestThumbs);

            $categoryTestedThumbs = isset($categoriesTestedThumbs[$key]) ? (int) $categoriesTestedThumbs[$key] : 0;
            $report->setTestedThumbs($categoryTestedThumbs);

            if (isset($categoriesAutopostingThumbs[$key])) {
                $report->setAutopostingThumbs((int) $categoriesAutopostingThumbs[$key]);
                $report->setUntilNowTotalThumbs($categoryTotalThumbs - $categoriesAutopostingThumbs[$key]);
            } else {
                $report->setUntilNowTotalThumbs($categoryTotalThumbs);
            }

            $categoriesReports[] = $report;
        }

        return $categoriesReports;
    }

    /**
     * Подсчитывает все активные тумбы в таблице ротации.
     *
     * @return integer
     */
    protected function calculateTotalThumbs(): int
    {
        $num = RotationStats::find()
            ->alias('gs')
            ->innerJoin(['g' => 'galleries'], '{{gs}}.{{gallery_id}}={{g}}.{{gallery_id}}')
            ->where(['{{g}}.{{status}}' => Gallery::STATUS_ACTIVE])
            ->count();

        return $num;
    }

    /**
     * Подсчитывает протестированные активные тумбы в таблице ротации.
     *
     * @return integer
     */
    protected function calculateTestThumbs(): int
    {
        $num = RotationStats::find()
            ->alias('gs')
            ->innerJoin(['g' => 'galleries'], '{{gs}}.{{gallery_id}}={{g}}.{{gallery_id}} AND {{gs}}.{{image_id}}={{g}}.{{image_id}}')
            ->where(['{{gs}}.{{is_tested}}' => 0, '{{g}}.{{status}}' => Gallery::STATUS_ACTIVE])
            ->count();

        return $num;
    }

    /**
     * Подсчитывает нетестированные активные тумбы в таблице ротации.
     *
     * @return integer
     */
    protected function calculateTestedThumbs(): int
    {
        $num = RotationStats::find()
            ->alias('gs')
            ->innerJoin(['g' => 'galleries'], '{{gs}}.{{gallery_id}}={{g}}.{{gallery_id}} AND {{gs}}.{{image_id}}={{g}}.{{image_id}}')
            ->where(['{{gs}}.{{is_tested}}' => 1, '{{g}}.{{status}}' => Gallery::STATUS_ACTIVE])
            ->count();

        return $num;
    }

    /**
     * Подсчитывает нетестированные активные тумбы в таблице ротации.
     *
     * @return integer
     */
    protected function calculateTestedZeroCtrThumbs(): int
    {
        $num = RotationStats::find()
            ->alias('gs')
            ->innerJoin(['g' => 'galleries'], '{{gs}}.{{gallery_id}}={{g}}.{{gallery_id}} AND {{gs}}.{{image_id}}={{g}}.{{image_id}}')
            ->where(['{{gs}}.{{is_tested}}' => 1, '{{gs}}.{{ctr}}' => 0,  '{{g}}.{{status}}' => Gallery::STATUS_ACTIVE])
            ->count();

        return $num;
    }

    /**
     * Подсчитывает все активные тумбы в таблице ротации группируя по категориям.
     *
     * @return array
     */
    protected function calculateCategoriesTotalThumbs(): array
    {
        $totalThumbs = RotationStats::find()
            ->select(new Expression('COUNT(*) as cnt'))
            ->alias('gs')
            ->innerJoin(['g' => 'galleries'], '{{gs}}.{{gallery_id}}={{g}}.{{gallery_id}} AND {{gs}}.{{image_id}}={{g}}.{{image_id}}')
            ->where(['{{g}}.{{status}}' => Gallery::STATUS_ACTIVE])
            ->groupBy('{{gs}}.{{category_id}}')
            ->indexBy('category_id')
            ->column();

        return $totalThumbs;
    }

    /**
     * Подсчитывает все активные тумбы в таблице ротации группируя по категориям.
     *
     * @return array
     */
    protected function calculateCategoriesUntilNowThumbs(): array
    {
        $totalThumbs = RotationStats::find()
            ->select(new Expression('COUNT(*) as cnt'))
            ->alias('gs')
            ->innerJoin(['g' => 'galleries'], '{{gs}}.{{gallery_id}}={{g}}.{{gallery_id}} AND {{gs}}.{{image_id}}={{g}}.{{image_id}}')
            ->where(['{{g}}.{{status}}' => Gallery::STATUS_ACTIVE])
            ->andWhere(['<=', '{{g}}.{{published_at}}', new Expression('NOW()')])
            ->groupBy('{{gs}}.{{category_id}}')
            ->indexBy('category_id')
            ->column();

        return $totalThumbs;
    }

    /**
     * Подсчитывает тумбы в автопостинге группируя по категориям.
     *
     * @return array
     */
    protected function calculateCategoriesAutopostingThumbs(): array
    {
        $totalThumbs = RotationStats::find()
            ->select(new Expression('COUNT(*) as cnt'))
            ->alias('gs')
            ->innerJoin(['g' => 'galleries'], '{{gs}}.{{gallery_id}}={{g}}.{{gallery_id}} AND {{gs}}.{{image_id}}={{g}}.{{image_id}}')
            ->where(['{{g}}.{{status}}' => Gallery::STATUS_ACTIVE])
            ->andWhere(['>=', '{{g}}.{{published_at}}', new Expression('NOW()')])
            ->groupBy('{{gs}}.{{category_id}}')
            ->indexBy('category_id')
            ->column();

        return $totalThumbs;
    }

    /**
     * Подсчитывает нетестированные тумбы в таблице ротации группируя по категориям.
     *
     * @return array
     */
    protected function calculateCategoriesTestThumbs(): array
    {
        $testedThumbs = RotationStats::find()
            ->select(new Expression('COUNT(*) as cnt'))
            ->alias('gs')
            ->innerJoin(['g' => 'galleries'], '{{gs}}.{{gallery_id}}={{g}}.{{gallery_id}} AND {{gs}}.{{image_id}}={{g}}.{{image_id}}')
            ->andWhere(['{{gs}}.{{is_tested}}' => 0])
            ->andWhere(['<=', '{{g}}.{{published_at}}', new Expression('NOW()')])
            ->andWhere(['{{g}}.{{status}}' => Gallery::STATUS_ACTIVE])
            ->groupBy('{{gs}}.{{category_id}}')
            ->indexBy('category_id')
            ->column();

        return $testedThumbs;
    }

    /**
     * Подсчитывает протестированные тумбы в таблице ротации группируя по категориям.
     *
     * @return array
     */
    protected function calculateCategoriesTestedThumbs(): array
    {
        $testedThumbs = RotationStats::find()
            ->select(new Expression('COUNT(*) as cnt'))
            ->alias('gs')
            ->innerJoin(['g' => 'galleries'], '{{gs}}.{{gallery_id}}={{g}}.{{gallery_id}} AND {{gs}}.{{image_id}}={{g}}.{{image_id}}')
            ->andWhere(['{{gs}}.{{is_tested}}' => 1])
            ->andWhere(['<=', '{{g}}.{{published_at}}', new Expression('NOW()')])
            ->andWhere(['{{g}}.{{status}}' => Gallery::STATUS_ACTIVE])
            ->groupBy('{{gs}}.{{category_id}}')
            ->indexBy('category_id')
            ->column();

        return $testedThumbs;
    }
}
