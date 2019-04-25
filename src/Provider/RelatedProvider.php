<?php
namespace SK\GalleryModule\Provider;

use Yii;
use yii\db\Expression;
use SK\GalleryModule\Model\Gallery;
use RS\Component\Core\Settings\SettingsInterface;
use SK\GalleryModule\Model\GalleriesRelated;
use SK\GalleryModule\Model\GalleriesCategoriesMap;

/**
 * RelatedFinder содержит методы для поиска похожих роликов.
 */
class RelatedProvider
{
    private $requiredRelatedNum;

    private $settings;

    const RELATED_NUMBER = 12;

    public function __construct()
    {
        $this->settings = Yii::$container->get(SettingsInterface::class);
    }

    public function getFromTable(int $gallery_id)
    {
        $requiredRelatedNum = $this->settings->get('related_number', self::RELATED_NUMBER, 'gallery');
            //SELECT `v`.* FROM `galleries_related` AS `r` LEFT JOIN `galleries` AS `v` ON `v`.`gallery_id` = `r`.`related_id` WHERE `r`.`gallery_id`=10
        $galleries = Gallery::find()
            ->select(['g.gallery_id', 'g.image_id', 'g.slug', 'g.title', 'g.orientation', 'g.likes', 'g.dislikes', 'g.images_num', 'g.comments_num', 'g.views', 'g.published_at'])
            ->from(['g' => Gallery::tableName()])
            ->leftJoin(['r' => GalleriesRelated::tableName()], 'g.gallery_id = r.related_id')
            ->with(['categories' => function ($query) {
                $query->select(['category_id', 'title', 'slug', 'h1'])
                    ->where(['enabled' => 1]);
            }])
            ->with(['coverImage' => function ($query) {
                $query->select(['image_id', 'gallery_id', 'path', 'source_url'])
                    ->where(['enabled' => 1]);
            }])
            ->where(['r.gallery_id' => $gallery_id])
            ->andWhere(['<=', 'g.published_at', new Expression('NOW()')])
            ->andWhere(['g.status' => Gallery::STATUS_ACTIVE])
            ->limit($requiredRelatedNum)
            //->asArray()
            ->all();

        return $galleries;
    }

    public function getModels(int $gallery_id)
    {
        $requiredRelatedNum = $this->settings->get('related_number', self::RELATED_NUMBER, 'gallery');

        $related = $this->getFromTable($gallery_id);

        $relatedNum = count($related);

        if (empty($related) || $relatedNum < $requiredRelatedNum) {
            $this->findAndSaveRelatedIds($gallery_id);
            $related = $this->getFromTable($gallery_id);
        }

        return $related;
    }

    public function findAndSaveRelatedIds(int $gallery_id)
    {
        $allowCategories = $this->settings->get('related_allow_categories', false, 'gallery');
        $allowDescription = $this->settings->get('related_allow_description', false, 'gallery');
        $requiredRelatedNum = $this->settings->get('related_number', self::RELATED_NUMBER, 'gallery');

        $query = Gallery::find()
            ->select(['gallery_id', 'title', 'description'])
            ->where(['gallery_id' => $gallery_id])
            ->asArray();

        if ($allowCategories) {
            $query
                ->with(['categories' => function ($query) {
                    $query->select(['category_id'])
                        ->where(['enabled' => 1]);
                }]);
        }

        $gallery = $query->one();

        if (null === $gallery) {
            return;
        }

        $searchString = $gallery['title'];

        if ($allowDescription) {
            $searchString .= " {$gallery['description']}]";
        }

        $relatedModels = Gallery::find()
            ->select(['`g`.`gallery_id`', 'MATCH(`title`, `description`) AGAINST (:query) AS `relevance`'])
            ->from (['g' => Gallery::tableName()]);

        if ($allowCategories && !empty($gallery['categories'])) {
                // выборка всех идентификаторов категорий поста.
            $categoriesIds = array_column($gallery['categories'], 'category_id');
            $relatedModels
                ->leftJoin(['gcm' => GalleriesCategoriesMap::tableName()], 'g.gallery_id = gcm.gallery_id')
                ->andWhere(['gcm.category_id' => $categoriesIds]);
        }

        $relatedGalleries = $relatedModels
            ->andWhere('MATCH(`title`, `description`) AGAINST (:query)', [':query' => $searchString])
            ->andWhere('`g`.`gallery_id`<>:gallery_id', [':gallery_id' => $gallery['gallery_id']])
            ->andWhere(['<=', '`g`.`published_at`', new Expression('NOW()')])
            ->andWhere(['`g`.`status`' => Gallery::STATUS_ACTIVE])
            ->groupBy('`g`.`gallery_id`')
            ->orderBy(['relevance' => SORT_DESC])
            ->limit($requiredRelatedNum)
            ->asArray()
            ->all();

        $related = [];
        foreach ($relatedGalleries as $relatedGallery) {
            $related[] = [$gallery['gallery_id'], $relatedGallery['gallery_id']];
        }

            // Удалим старое.
        Yii::$app->db->createCommand()
            ->delete(GalleriesRelated::tableName(), ['gallery_id' => $gallery['gallery_id']])
            ->execute();

            // вставим новое
        Yii::$app->db->createCommand()
            ->batchInsert(GalleriesRelated::tableName(), ['gallery_id', 'related_id'], $related)
            ->execute();
    }
}
