<?php
namespace SK\GalleryModule\Command;

use yii\console\Controller;
use SK\GalleryModule\Service\Gallery as GalleryService;

/**
 * This command echoes the first argument that you have entered.
 */
class GalleryController extends Controller
{
    /**
     * Индекс
     */
    public function actionIndex()
    {
        echo 'Hello';
    }

    /**
     * Обновляет максимальный цтр видео.
     * 
     * @return void
     */
    public function actionUpdateMaxCtr()
    {
        $videoService = new GalleryService();
        $videoService->updateMaxCtr();
    }
}
