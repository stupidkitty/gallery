<?php
namespace SK\GalleryModule\Model;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "galleries_categories".
 *
 * @property integer $category_id
 * @property integer $image_id
 * @property integer $ordering
 * @property string $slug
 * @property string $meta_title
 * @property string $meta_description
 * @property string $title
 * @property string $h1
 * @property string $description
 * @property string $seotext
 * @property string $param1
 * @property string $param2
 * @property string $param3
 * @property integer $galleries_num
 * @property double $popularity
 * @property integer $on_index
 * @property boolean $enabled
 * @property string $created_at
 * @property string $updated_at
 *
 * @property GalleriesCategoriesMap[] $galleriesCategoriesMap
 * @property Gallery[] $galleries
 * @property RotationStats[] $rotationStats
 */
class Category extends ActiveRecord implements CategoryInterface, ToggleableInterface, SlugAwareInterface
{
    use SlugGeneratorTrait, ToggleableTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'galleries_categories';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                [
                    'title',
                    'slug',
                    'h1',
                    'meta_description',
                    'meta_title',
                    'description',
                    'seotext',
                    'param1',
                    'param2',
                    'param3'
                ],
                'string'
            ],
            [['slug'], 'unique'],
            [
                [
                    'image_id',
                    'ordering',
                    'galleries_num',
                ],
                'integer'
            ],
            [['popularity'], 'number'],
            [['on_index', 'enabled'], 'boolean'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGalleries()
    {
        return $this->hasMany(Gallery::class, ['gallery_id' => 'gallery_id'])->viaTable(GalleriesCategoriesMap::tableName(), ['category_id' => 'category_id']);
    }

    /**
     * @return boolean
     */
    public function hasCoverImage(): bool
    {
        return null !== $this->coverImage;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCoverImage()
    {
        return $this->hasOne(Image::class, ['image_id' => 'image_id']);
    }

    /**
     * @inheritdoc
     */
    public function setCoverImage(ImageInterface $image): void
    {
        $this->link('coverImage', $image);
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        return $this->category_id;
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @inheritdoc
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @inheritdoc
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @inheritdoc
     */
    public function setSlug(string $slug)
    {
        $this->slug = $slug;
    }
}
