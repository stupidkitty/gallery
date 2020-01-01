<?php
namespace SK\GalleryModule\Model;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "galleries_related_map".
 *
 * @property integer $gallery_id
 * @property integer $related_id
 *
 * @property Gallery $gallery
 * @property Gallery $related
 */
class GalleriesRelated extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'galleries_related';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['gallery_id', 'related_id'], 'required'],
            [['gallery_id', 'related_id'], 'integer'],
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
    public function getRelated()
    {
        return $this->hasOne(Gallery::class, ['gallery_id' => 'related_id']);
    }
}
