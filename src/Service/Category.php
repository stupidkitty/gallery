<?php
namespace SK\GalleryModule\Service;

use Yii;
use SK\GalleryModule\Model\Image;
use SK\GalleryModule\Model\Category as CategoryModel;
use RS\Component\Core\Settings\SettingsInterface;

use SK\GalleryModule\Provider\RotateGalleryProvider;

class Category
{

    public function countGalleries()
    {
        $sql = "
            UPDATE `galleries_categories` as `gc`
            LEFT JOIN (
                SELECT `category_id`, COUNT(*) as `galleries_num`
                FROM `galleries_categories_map`
                LEFT JOIN `galleries` ON `galleries`.`gallery_id` = `galleries_categories_map`.`gallery_id`
                WHERE `galleries`.`published_at` < NOW() AND `galleries`.`status` = 10
                GROUP BY `category_id`
            ) as `gcm` ON `gc`.`category_id`=`gcm`.`category_id`
            SET `gc`.`galleries_num` = IFNULL(`gcm`.`galleries_num`, 0)
        ";

        return Yii::$app->db->createCommand($sql)
            ->execute();
    }

    /**
     * SET @total_clicks = (SELECT SUM(`clicks`) FROM `galleries_categories_stats` WHERE `date` >= (NOW() - INTERVAL 2 DAY));
     *
     * SELECT `category_id`, (SUM(`clicks`) / @total_clicks) * 100
     * FROM `galleries_categories_stats`
     * WHERE `date` >= (NOW() - INTERVAL 2 DAY)
     * GROUP BY `category_id`;
    */
    public function updatePopularity()
    {
        /*$totalClicks = Yii::$app->db
            ->createCommand('SELECT SUM(`clicks`) FROM galleries_categories_stats` WHERE `date` >= (NOW() - INTERVAL 2 DAY)')
            ->queryScalar();

        if (0 === $totalClicks) {
            $totalClicks = 1;
        }


        $sql = "
            UPDATE `galleries_categories` as `gc`
            LEFT JOIN (
                SELECT `category_id`, ((SUM(`clicks`) / :total_clicks) * 100) as `clicks_summary`
                FROM `galleries_categories_stats`
                WHERE `date` >= (NOW() - INTERVAL 2 DAY)
                GROUP BY `category_id`
            ) as `gcs` ON `gc`.`category_id`=`gcs`.`category_id`
            SET `gc`.`popularity` = IFNULL(`gcs`.`clicks_summary`, 0)
        ";*/

        $sql = "
            UPDATE `galleries_categories` as `gc`
            LEFT JOIN (
                SELECT `z`.`category_id`, ROUND(((`z`.`category_clicks` / IFNULL(`z`.`total_clicks`, 1)) * 100), 2) AS `popularity`
                FROM (
                    SELECT DISTINCT `gc2`.`category_id`,
                        SUM(`gcs2`.`clicks`) OVER (PARTITION BY `gc2`.`category_id`) AS `category_clicks`,
                        SUM(`gcs2`.`clicks`) OVER (PARTITION BY `gcs2`.`date`) AS `total_clicks`
                    FROM `galleries_categories` AS `gc2`
                    LEFT JOIN `galleries_categories_stats` AS `gcs2` USING (`category_id`)
                    WHERE `gcs2`.`date` >= (CURDATE() - INTERVAL 1 DAY) AND `gcs2`.`hour` >= HOUR(CURTIME())
                ) AS `z`
            ) as `x` USING (`category_id`)
            SET `gc`.`popularity` = IFNULL(`x`.`popularity`, 0)
        ";

        return Yii::$app->db->createCommand($sql)
            //->bindValue(':total_clicks', $totalClicks)
            ->execute();
    }

    /**
     * Вынести в отдельный класс, переписать логику установки тумбы.
     * Достаточно взять первые пять тумб.
     */
    public function assignCoverImages()
    {
        $settings = Yii::$container->get(SettingsInterface::class);

        $categories = CategoryModel::find()
            ->select(['category_id', 'title'])
            ->where(['enabled' => 1])
            ->all();

        if (empty($categories)) {
            return;
        }

        //SELECT `image_id` FROM `galleries_stats` WHERE (`category_id`=20) AND (`best_image`=1) AND `image_id` NOT IN (1,2,3) ORDER BY `ctr` LIMIT 1
        $usedImagesIds = [];

        foreach ($categories as $category) {
            // Выбрать тумбы с первой страницы категории
            $dataProvider = new RotateGalleryProvider([
                'pagination' => [
                    'defaultPageSize' => (int) $settings->get('items_per_page', 24, 'gallery'),
                    'pageSize' => (int) $settings->get('items_per_page', 24, 'gallery'),
                    'forcePageParam' => false,
                ],
                'sort' => false,
                'category_id' => $category->getId(),
                'testPerPagePercent' => (int) $settings->get('test_items_percent', 15, 'gallery'),
                'testGalleriesStartPosition' => (int) $settings->get('test_items_start', 3, 'gallery'),
            ]);

            $imagesIds = array_column($dataProvider->getModels(), 'image_id');

            if (empty($imagesIds)) {
                continue;
            }

            // Отсеять уже использованные в других категориях (уникальные должны быть)
            $unusedIds = array_diff($imagesIds, $usedImagesIds);

            // Если уникальные иды остались, то выбрать первую и установить ее как обложку категории.
            if (!empty($unusedIds)) {
                $firstId = array_shift($unusedIds);
                $image = Image::findOne(['image_id' => $firstId, 'enabled' => 1]);

                if (null !== $image) {
                    $category->setCoverImage($image);
                }

                // Записать, что данная тумба уже используется.
                $usedImagesIds[] = $image->getId();
            }
        }
    }

    /**
     * Удаляет старую статистику по кликам в категорию.
     *
     * @return void
     */
    public function clearOldStats()
    {
        $db = Yii::$app->get('db');

        // Удаление статы кликов по категориям, которые старше 1 месяца
        $db->createCommand()
            ->delete('galleries_categories_stats', '`date` < (NOW() - INTERVAL 1 MONTH)')
            ->execute();
    }
}
