<?php
namespace SK\GalleryModule\Model;

interface CategoryInterface
{
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
}
