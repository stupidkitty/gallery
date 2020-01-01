<?php
namespace SK\GalleryModule\Command;

use yii\console\Controller;
use SK\GalleryModule\Service\Category;

/**
 * This command echoes the first argument that you have entered.
 */
class CategoryController extends Controller
{
    /**
     * Пересчитывает популярность категории.
     */
    public function actionUpdatePopularity()
    {
        $categoryService = new Category();
        $categoryService->updatePopularity();
    }

    /**
     * Пересчитывает активные Галереи в категория.
     */
    public function actionCountGalleries()
    {
        $categoryService = new Category();
        $categoryService->countGalleries();
    }

    /**
     * Устанавливает главные тумбы у категории
     */
    public function actionAssignCoverImages()
    {
        $categoryService = new Category();
        $categoryService->assignCoverImages();
    }

    /**
     * Удаляет старую статистику по кликам в категорию
     */
    public function actionClearOldStats()
    {
        $categoryService = new Category();
        $categoryService->clearOldStats();
    }
}
