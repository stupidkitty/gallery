<?php
namespace SK\GalleryModule\Model;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "galleries_categories_stats".
 *
 * @property string $date
 * @property integer $hour
 * @property integer $clicks
 */
class GalleriesCategoriesStats extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'galleries_categories_stats';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['hour','clicks'],'integer'],
            [['date'], 'safe'],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::class, ['category_id' => 'category_id']);
    }
}
