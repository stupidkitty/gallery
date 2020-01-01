<?php
namespace SK\GalleryModule\Model;

interface ImageInterface
{
    /**
     * Возвращает чистовой идентификатор.
     *
     * @return integer
     */
    public function getId(): ?int;

    /**
     * Возвращает короткий путь фотографии.
     *
     * @return string
     */
    public function getPath(): ?string;

    /**
     * Устанавливает короткий путь изображения.
     *
     * @param string $path Короткий путь к файлу фотографии.
     */
    public function setPath(string $path): void;
}
