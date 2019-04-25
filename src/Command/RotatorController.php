<?php
namespace SK\GalleryModule\Command;

use yii\console\Controller;
use SK\GalleryModule\Service\Rotator as RotatorService;

/**
 * This command echoes the first argument that you have entered.
 */
class RotatorController extends Controller
{
    /**
     * Индекс
     */
    public function actionIndex()
    {
        echo 'Hello';
    }

    /**
     * Помечает тумбы как тестированные в статистике.
     * 
     * @return void
     */
    public function actionMarkTested()
    {
        $rotatorService = new RotatorService();
        $rotatorService->markAsTestedRows();
    }

    /**
     * Смещает указатель истори просмотров на следующий.
     * 
     * @return void
     */
    public function actionShiftViews()
    {
        $rotatorService = new RotatorService();
        $rotatorService->shiftHistoryCheckpoint();
    }
}
