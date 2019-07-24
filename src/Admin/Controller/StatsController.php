<?php
namespace SK\GalleryModule\Admin\Controller;

use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use SK\GalleryModule\Statistic\GalleryStatisticBuilder;
use SK\GalleryModule\Statistic\RotationStatisticBuilder;

class StatsController extends Controller
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
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $reportBuilder = new RotationStatisticBuilder;
        $report = $reportBuilder->build();
        $galleryReportBuilder = new GalleryStatisticBuilder;
        $galleryReport = $galleryReportBuilder->build();

        return $this->render('index', [
            'galleryReport' => $galleryReport,
            'report' => $report,
        ]);
    }
}
