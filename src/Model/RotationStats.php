<?php
namespace SK\GalleryModule\Model;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "galleries_stats".
 *
 * @property integer $category_id
 * @property integer $gallery_id
 * @property integer $image_id
 * @property integer $is_tested
 * @property integer $shows
 * @property integer $clicks
 * @property double $ctr
 *
 * @property Gallery $gallery
 * @property Category $category
 * @property Image $image
 */
class RotationStats extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'galleries_stats';
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['category_id', 'image_id', 'gallery_id'], 'required'],
            [
                [
                    'category_id',
                    'gallery_id',
                    'image_id',
                    'current_shows',
                    'current_clicks',
                    'shows0',
                    'clicks0',
                    'shows1',
                    'clicks1',
                    'shows2',
                    'clicks2',
                    'shows3',
                    'clicks3',
                    'shows4',
                    'clicks4',
                ],
                'integer'
            ],
            [['is_tested'], 'boolean'],
            [['ctr'], 'number'],
        ];
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGallery()
    {
        return $this->hasOne(Gallery::class, ['gallery_id' => 'gallery_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::class, ['category_id' => 'category_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImage()
    {
        return $this->hasOne(Image::class, ['image_id' => 'image_id']);
    }
    /**
     * @inheritdoc
     */
    public static function addGallery(Category $category, Gallery $gallery, Image $image): void
    {
        $exists = self::find()
            ->where(['gallery_id' => $gallery->getId(), 'image_id' => $image->getId(), 'category_id' => $category->getId()])
            ->exists();

        if ($exists) {
            return;
        }

        $rotationStats = new static();

        $rotationStats->gallery_id = $gallery->getId();
        $rotationStats->category_id = $category->getId();
        $rotationStats->image_id = $image->getId();

        $rotationStats->save();
    }
}
