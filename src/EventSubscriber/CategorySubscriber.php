<?php
namespace SK\GalleryModule\EventSubscriber;

//use SK\GalleryModule\Model\RotationStats;
use SK\GalleryModule\Model\GalleriesCategoriesMap;

final class CategorySubscriber
{
    /**
     * Событие должно подключаться к обновляемому объекту.
     */
    public static function onCreate($event)
    {
        $event->sender->updated_at = gmdate('Y-m-d H:i:s');
        $event->sender->created_at = gmdate('Y-m-d H:i:s');
    }

    /**
     * Событие должно подключаться к обновляемому объекту.
     */
    public static function onUpdate($event)
    {
        $event->sender->updated_at = gmdate('Y-m-d H:i:s');
    }

    /**
     * Событие должно подключаться к удаляемому объекту.
     */
    public static function onDelete($event)
    {
        $categoryId = $event->sender->getId();

        GalleriesCategoriesMap::deleteAll(['category_id' => $categoryId]);
        //RotationStats::deleteAll(['category_id' => $categoryId]);
    }
}
