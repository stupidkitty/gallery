<?php
namespace SK\GalleryModule\Model;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "galleries_crops".
 *
 * @property integer $crop_profile_id
 * @property string $name
 * @property string $comment
 * @property string $command
 * @property boolean $enabled
 * @property string $created_at
 */
class CropProfile extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'galleries_crops';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name', 'comment', 'command'], 'string'],
            [['created_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        return $this->crop_id;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @inheritdoc
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @inheritdoc
     */
    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    /**
     * @inheritdoc
     */
    public function getCommand(): ?string
    {
        return $this->command;
    }

    /**
     * @inheritdoc
     */
    public function setCommand(string $command): void
    {
        $this->command = $command;
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
}
