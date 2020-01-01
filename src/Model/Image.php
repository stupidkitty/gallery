<?php
namespace SK\GalleryModule\Model;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "galleries_images".
 *
 * @property integer $image_id
 * @property integer $gallery_id
 * @property integer $ordering
 * @property string $hash
 * @property string $path
 * @property string $source_url
 * @property boolean $enabled
 * @property string $created_at
 *
 * @property Gallery $gallery
 * @property RotationStats[] $rotationStats
 */
class Image extends ActiveRecord implements ImageInterface, ToggleableInterface
{
    use ToggleableTrait;

    protected $file;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'galleries_images';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['gallery_id', 'ordering'], 'integer'],
            [['enabled'], 'boolean'],
            [['hash', 'path', 'source_url'], 'string'],
            [['created_at'], 'safe'],
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
     * @inheritdoc
     */
    public function setGallery(GalleryInterface $gallery): void
    {
        $this->link('gallery', $gallery);
    }

    /**
     * @return boolean
     */
    public function hasGallery(): bool
    {
        return !empty($this->gallery);
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        return $this->image_id;
    }

    /**
     * @inheritdoc
     */
    public function getGalleryId(): int
    {
        return $this->gallery_id;
    }

    /**
     * @inheritdoc
     */
    public function setGalleryId(int $gallery_id): void
    {
        $this->gallery_id = $gallery_id;
    }

    /**
     * @inheritdoc
     */
    public function getOrdering(): int
    {
        return $this->ordering;
    }

    /**
     * @inheritdoc
     */
    public function setOrdering(int $ordering): void
    {
        $this->ordering = $ordering;
    }

    /**
     * @inheritdoc
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * @inheritdoc
     */
    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    /**
     * @inheritdoc
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @inheritdoc
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @inheritdoc
     */
    public function getSourceUrl(): ?string
    {
        return $this->source_url;
    }

    /**
     * @inheritdoc
     */
    public function setSourceUrl(string $source_url): void
    {
        $this->source_url = $source_url;
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): string
    {
        return $this->created_at;
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(string $created_at): void
    {
        $this->created_at = $created_at;
    }

    /**
     * @inheritdoc
     */
    public function getFile(): ?\SplFileInfo
    {
        return $this->file;
    }

    /**
     * @inheritdoc
     */
    public function setFile(?\SplFileInfo $file): void
    {
        $this->file = $file;
    }
}
