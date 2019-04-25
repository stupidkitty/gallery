<?php
namespace SK\GalleryModule\Admin;

use Yii;
use yii\web\Response;
use yii\db\Expression;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\filters\ContentNegotiator;
use SK\GalleryModule\Model\Image;
use SK\GalleryModule\Model\Gallery;
use SK\GalleryModule\Model\Category;
use SK\GalleryModule\Model\RotationStats;
use SK\GalleryModule\Model\GalleriesRelated;
use SK\GalleryModule\Model\GalleriesCategoriesMap;
use SK\GalleryModule\Model\GalleriesCategoriesStats;
use SK\GalleryModule\Service\Category as CategoryService;

/**
 * ToolsController это всякие инструменты.
 */
class ToolsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
           'access' => [
               'class' => AccessControl::class,
               'rules' => [
                   [
                       'allow' => true,
                       'roles' => ['@'],
                   ],
               ],
           ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'clear-stats' => ['post'],
                    'random-date' => ['post'],
                    'clear-galleries' => ['post'],
                    'clear-related' => ['post'],
                    'recalculate-categories-galleries' => ['post'],
                    'set-categories-thumbs' => ['post'],
                ],
            ],
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'only' => [
                    'clear-stats',
                    'random-date',
                    'clear-galleries',
                    'clear-related',
                    'recalculate-categories-galleries',
                    'set-categories-thumbs',
                ],
                'formatParam' => '_format',
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;

        return parent::beforeAction($action);
    }

    /**
     * Выводит форму с различными действиями для видео роликов.
     * @return mixed
     */
    public function actionIndex()
    {

        return $this->render('index', [
        ]);
    }

    /**
     * Очищает статистику по галереям (показы, просмотры и т.д.)
     * @return json
     */
    public function actionClearStats()
    {
         try {
             // Очистка статистики тумб
            RotationStats::updateAll([
                'is_tested' => 0,
                'current_index' => 0,
                'current_shows' => 0,
                'current_clicks' => 0,
                'shows0' => 0,
                'clicks0' => 0,
                'shows1' => 0,
                'clicks1' => 0,
                'shows2' => 0,
                'clicks2' => 0,
                'shows3' => 0,
                'clicks3' => 0,
                'shows4' => 0,
                'clicks4' => 0,
            ]);

            // Очитска просмотров, лайков, дизлайков.
            Gallery::updateAll([
                'likes' => 0,
                'dislikes' => 0,
                'views' => 0,
            ]);

            // Очистка статистики категорий
            Category::updateAll(['popularity' => 0]);
            Yii::$app->db->createCommand()
                ->truncateTable(GalleriesCategoriesStats::tableName())
                ->execute();

            return [
                'message' => 'Galleries statistic cleared',
            ];
        } catch (\Throwable $e) {
            return [
                'error' => [
                    'code' => 1,
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Creates a new GalelriesCategories model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return json
     */
    public function actionRandomDate()
    {
        try {
                // Рандом для видео в таблице `galleries`
            Gallery::updateAll([
                'published_at' => new Expression('FROM_UNIXTIME(UNIX_TIMESTAMP(NOW()) - FLOOR(0 + (RAND() * 31536000)))'),
            ]);

            return [
                'message' => 'All galleries published date randomized',
            ];
        } catch (\Throwable $e) {
            return [
                'error' => [
                    'code' => 1,
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Удаляет все галереи.
     *
     * @return json
     */
    public function actionClearGalleries()
    {
        try {
                // Очищаем стату тумб
            Yii::$app->db->createCommand()
                ->truncateTable(RotationStats::tableName())
                ->execute();

                // Очищаем стату категорий
            Yii::$app->db->createCommand()
                ->truncateTable(GalleriesCategoriesStats::tableName())
                ->execute();

                // Очищаем релатеды.
            Yii::$app->db->createCommand()
                ->truncateTable(GalleriesRelated::tableName())
                ->execute();

                // Очищаем таблица отношений категория - галерея.
            Yii::$app->db->createCommand()
                ->truncateTable(GalleriesCategoriesMap::tableName())
                ->execute();

                // Удаляем фотки
            Yii::$app->db->createCommand()
                ->truncateTable(Image::tableName())
                ->execute();

                // Удаляем галлереи
            Yii::$app->db->createCommand()
                ->truncateTable(Gallery::tableName())
                ->execute();

            return [
                'message' => 'All galleries deleted.\nPlease, delete file storage manually.',
            ];
        } catch (\Throwable $e) {
            return [
                'error' => [
                    'code' => 1,
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Очищает таблицу "похожие галереи".
     *
     * @return json
     */
    public function actionClearRelated()
    {
        try {
            Yii::$app->db->createCommand()
                ->truncateTable(GalleriesRelated::tableName())
                ->execute();

            return [
                'message' => 'Related galleries cleared',
            ];
        } catch (\Throwable $e) {
            return [
                'error' => [
                    'code' => 1,
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Пересчитывает количество активных галерей в категориях.
     *
     * @return json
     */
    public function actionRecalculateCategoriesGalleries()
    {
        try {
            $categoryManager = new CategoryService;
            $categoryManager->countGalleries();

            return [
                'message' => 'All active galleries counted in categories',
            ];
        } catch (\Throwable $e) {
            return [
                'error' => [
                    'code' => 1,
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Установка тумб у категорий по данным ротации
     *
     * @return json
     */
    public function actionSetCategoriesThumbs()
    {
        try {
            $categoryManager = new CategoryService;
            $categoryManager->assignCoverImages();

            return [
                'message' => 'New thumbs in categories set up',
            ];
        } catch (\Throwable $e) {
            return [
                'error' => [
                    'code' => 1,
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }
}
