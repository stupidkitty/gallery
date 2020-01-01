<?php
namespace SK\GalleryModule\Model;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use RS\Component\User\Model\User;
use SK\GalleryModule\Query\GalleryQuery;

/**
 * This is the model class for table "galleries".
 *
 * @property integer $gallery_id
 * @property integer $image_id
 * @property integer $user_id
 * @property string $slug
 * @property string $title
 * @property string $description
 * @property integer $orientation
 * @property integer $on_index
 * @property integer $likes
 * @property integer $dislikes
 * @property integer $images_num
 * @property integer $comments_num
 * @property integer $views
 * @property double $max_ctr
 * @property string $template
 * @property integer $status
 * @property timestamp $published_at
 * @property timestamp $created_at
 * @property timestamp $updated_at
 *
 * @property GalleriesCategoriesMap[] $galleriesCategoriesMaps
 * @property Category[] $categories
 * @property Image[] $images
 * @property Image $previewImage
 * @property RotationStats[] $rotationStats
 */
class Gallery extends ActiveRecord implements GalleryInterface, SlugAwareInterface
{
    use SlugGeneratorTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'galleries';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['slug', 'title', 'description', 'template'], 'string'],
            [['image_id', 'user_id', 'orientation', 'dislikes', 'images_num', 'comments_num', 'views', 'status'], 'integer'],
            [['max_ctr'], 'number'],
            [['published_at', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    public static function find()
    {
        return new GalleryQuery(get_called_class());
    }

    /**
     * @return boolean
     */
    public function hasUser(): bool
    {
        return null !== $this->user;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['user_id' => 'user_id']);
    }

    /**
     * @inheritdoc
     */
    public function setUser(IdentityInterface $user): void
    {
        $this->link('user', $user);
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
     * @return boolean
     */
    public function hasImages(): bool
    {
        return !empty($this->images);
    }

    /**
     * @return \yii\db\ActiveQuery[]
     */
    public function getImages()
    {
        return $this->hasMany(Image::class, ['gallery_id' => 'gallery_id']);
    }

    /**
     * @inheritdoc
     */
    public function addImage(ImageInterface $image): void
    {
        $this->link('images', $image);
    }

    /**
     * @inheritdoc
     */
    public function removeImage(ImageInterface $image): void
    {
        $this->unlink('images', $image);
    }

    /**
     * @return boolean
     */
    public function hasCategories(): bool
    {
        return !empty($this->categories);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategories()
    {
        return $this->hasMany(Category::class, ['category_id' => 'category_id'])
            ->viaTable(GalleriesCategoriesMap::tableName(), ['gallery_id' => 'gallery_id']);
    }

    /**
     * @inheritdoc
     */
    public function addCategory(CategoryInterface $category): void
    {
        $exists = GalleriesCategoriesMap::find()
            ->where(['gallery_id' => $this->gallery_id, 'category_id' => $category->category_id])
            ->exists();

        if (!$exists) {
            $this->link('categories', $category);
        }
    }

    /**
     * @inheritdoc
     */
    public function removeCategory(CategoryInterface $category): void
    {
        $this->unlink('categories', $category, true);
    }

    /**
     * Return list of status codes and labels
     *
     * @return array
     */
    public static function getStatusNames(): array
    {
        return [
            self::STATUS_DISABLED  => 'Отключено',
            self::STATUS_ACTIVE    => 'Опубликовано',
            self::STATUS_MODERATE  => 'На модерации',
            self::STATUS_DELETED   => 'Удалено',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        return $this->gallery_id;
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

    /**
     * @inheritdoc
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @inheritdoc
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @inheritdoc
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @inheritdoc
     */
    public function setTemplate(string $template)
    {
        $this->template = $template;
    }
}
