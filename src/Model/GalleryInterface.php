<?php
namespace SK\GalleryModule\Model;

interface GalleryInterface
{
    const STATUS_DISABLED = 0; // Отключено, новое
    const STATUS_ACTIVE = 10; // Опубликовано
    const STATUS_MODERATE = 20; // Модерация, проверка
    const STATUS_DELETED = 90; // Удалено.

    /**
     * Возвращает чистовой идентификатор.
     *
     * @return integer
     */
    public function getId(): ?int;

    /**
     * Возвращает название.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Устанавливает название.
     *
     * @param string $title Название галереи.
     */
    public function setTitle(string $title): void;

    /**
     * Проверка, установлено ли превью для галереи.
     *
     * @return bool
     */
    public function hasCoverImage(): bool;

    /**
     * Возврат превью.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCoverImage();

    /**
     * Установка превью.
     *
     * @inheritdoc
     */
    public function setCoverImage(ImageInterface $image): void;

    /**
     * @return bool
     */
    public function hasImages(): bool;

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImages();

    /**
     * @param ImageInterface $image
     */
    public function addImage(ImageInterface $image): void;

    /**
     * @param ImageInterface $image
     */
    public function removeImage(ImageInterface $image): void;

    /**
     * @return bool
     */
    public function hasCategories(): bool;

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategories();

    /**
     * @inheritdoc
     */
     public function addCategory(CategoryInterface $category): void;

    /**
     * @inheritdoc
     */
     public function removeCategory(CategoryInterface $category): void;

    /**
     * @inheritdoc
     */
     public static function getStatusNames(): array;
}
