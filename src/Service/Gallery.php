<?php
namespace SK\GalleryModule\Service;

use Yii;
use yii\helpers\ArrayHelper;
use SK\GalleryModule\Model\Category;
use SK\GalleryModule\Model\RotationStats;
use SK\GalleryModule\Model\GalleriesRelated;
use SK\GalleryModule\Model\GalleryInterface;
use SK\GalleryModule\Model\GalleriesCategoriesMap;
use SK\GalleryModule\Model\Gallery as GalleryModel;

class Gallery
{
    /*protected $filesystem;
    protected $imageFormatNames;

    public function __construct(FilesystemInterface $storage)
    {
        $this->storage = $storage;
    }*/

    public static function getQuery()
    {
        return GalleryModel::find();
    }

    public static function findById(int $id): ?Gallery
    {
        return GalleryModel::findOne($id);
    }

    /**
     * Удаляет галерею.
     */
    public function delete(GalleryInterface $gallery): bool
    {
        $imageService = Yii::$container->get(Image::class);

        foreach ($gallery->images as $image) {
            $imageService->delete($image);
        }

        GalleriesCategoriesMap::deleteAll(['gallery_id' => $gallery->getId()]);
        GalleriesRelated::deleteAll(['gallery_id' => $gallery->getId()]);
        GalleriesRelated::deleteAll(['related_id' => $gallery->getId()]);
        RotationStats::deleteAll(['gallery_id' => $gallery->getId()]);

        return $gallery->delete();
    }

    /**
     * Обвноляет список категорий у галереи
     */
    public function updateCategoriesByIds(GalleryInterface $gallery, array $newCategoriesIds)
    {
        $oldCategoriesIds = ArrayHelper::getColumn($gallery->categories, 'category_id');

        $removeCategoriesIds = array_diff($oldCategoriesIds, $newCategoriesIds);
        $addCategoriesIds = array_diff($newCategoriesIds, $oldCategoriesIds);

        if (!empty($removeCategoriesIds)) {
            $removeCategories = Category::find()
                ->where(['category_id' => $removeCategoriesIds])
                ->all();

            foreach ($removeCategories as $removeCategory) {
                $gallery->removeCategory($removeCategory);
                RotationStats::deleteAll(['gallery_id' => $gallery->getId(), 'category_id' => $removeCategory->getId()]);
            }
        }

        if (!empty($addCategoriesIds)) {
            $addCategories = Category::find()
                ->where(['category_id' => $addCategoriesIds])
                ->all();

            foreach ($addCategories as $addCategory) {
                $gallery->addCategory($addCategory);
            }
        }
    }

    /**
     * Обновляет максимальный цтр среди категорий и тумб.
     *
     * ```
     * UPDATE `galleries` AS `g`
     * LEFT JOIN (
     *     SELECT `gallery_id`, MAX(`ctr`) AS `max_ctr`
     *     FROM `galleries_stats`
     *     WHERE `ctr` != 0
     *     GROUP BY `gallery_id`
     * ) as `gs` ON `g`.`gallery_id` = `gs`.`gallery_id`
     * SET `g`.`max_ctr` = IFNULL(`gs`.`max_ctr`, 0)
     * WHERE `g`.`max_ctr`!=`gs`.`max_ctr`
     * ```
     * 
     * @return void
     */
    public function updateMaxCtr()
    {
        $sql = "
            UPDATE `galleries` AS `g`
            INNER JOIN (
                SELECT `gallery_id`, MAX(`ctr`) AS `max_ctr`
                FROM `galleries_stats`
                WHERE `ctr` != 0
                GROUP BY `gallery_id`
            ) as `gs` ON `g`.`gallery_id` = `gs`.`gallery_id`
            SET `g`.`max_ctr` = IFNULL(`gs`.`max_ctr`, 0)
            WHERE `g`.`max_ctr`!=`gs`.`max_ctr`
        ";

        Yii::$app->db
            ->createCommand($sql)
            ->execute();
    }
}
