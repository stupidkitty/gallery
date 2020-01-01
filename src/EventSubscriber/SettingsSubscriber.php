<?php
namespace SK\GalleryModule\EventSubscriber;

use Yii;

class SettingsSubscriber
{
    public static function onMenuInit($event)
    {
        $event->sender->addItem([
            'label' => 'Галереи',
            'group' => 'modules',
            'url' => ['/admin/gallery/settings/index'],
            'icon' => '<i class="fa fa-image"></i>',
            'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'gallery' && Yii::$app->controller->id === 'settings')
        ]);
    }
}
