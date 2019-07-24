<?php
namespace SK\GalleryModule\Admin\Controller;

use yii\web\Response;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\filters\ContentNegotiator;
use SK\GalleryModule\Model\RotationStats;

/**
 * RotationController Действия с таблицей ротации.
 */
class RotationController extends Controller
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
                    'restart-zero-ctr' => ['post'],
                ],
            ],
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'only' => [
                    'restart-zero-ctr',
                ],
                'formatParam' => '_format',
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * Отключает csrf для аякса
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;

        return parent::beforeAction($action);
    }

    /**
     * Перезапускает ротацию тестированных тумбы с 0 цтр.
     *
     * @return mixed
     */
    public function actionRestartZeroCtr()
    {
        try {
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
            ], [
                'is_tested' => 1,
                'ctr' => 0,
            ]);

            return $this->asJson([
                'message' => 'Ротация тестированных тумб с 0 ctr перезапущена',
            ]);
        } catch (\Exception $e) {
            return $this->asJson([
                'error' => [
                    'code' => 1,
                    'message' => $e->getMessage(),
                ],
            ]);
        }
    }
}
