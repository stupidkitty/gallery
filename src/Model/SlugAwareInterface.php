<?php
namespace SK\GalleryModule\Model;

interface SlugAwareInterface
{
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
     * @return string|null
     */
    public function getSlug(): string;

    /**
     * @param string|null $slug
     */
    public function setSlug(string $slug);
}
