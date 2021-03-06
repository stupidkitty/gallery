<?php
namespace SK\GalleryModule\Api\Controller;

use Yii;
use yii\filters\Cors;
use yii\db\Expression;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use yii\filters\auth\HttpBearerAuth;
use SK\GalleryModule\Model\Gallery;
use SK\GalleryModule\Model\Category;
use RS\Component\Core\Settings\SettingsInterface;

/**
 * Class InfoController
 *
 * @package SK\GalleryModule\Api\Controller
 */
class InfoController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['get'],
                ],
            ],
            'corsFilter' => [
                'class' => Cors::class,
            ],
            'authenticator' => [
                'class' => HttpBearerAuth::class,
            ],
        ];
    }

    /**
     * Show information about galleries
     *
     * @param SettingsInterface $settings
     * @return array
     */
    public function actionIndex(SettingsInterface $settings): array
    {
        $data = [];

        $data['total_galleries_num'] = Gallery::find()->count();
        $data['active_galleries_num'] = Gallery::find()->onlyActive()->count();

        $data['autoposting_queue_num'] = Gallery::find()
            ->andWhere(['>=', 'published_at', new Expression('NOW()')])
            ->onlyActive()
            ->count();

        $data['active_categories_num'] = Category::find()->where(['enabled' => 1])->count();
        $data['total_categories_num'] = Category::find()->count();

        $data['max_published_at'] = Gallery::find()->onlyActive()->max('published_at');
        $data['autoposting_interval'] = $settings->get('autoposting_fixed_interval', 8640, 'gallery');
        $data['autoposting_dispersion_interval'] = $settings->get('autoposting_spread_interval', 600, 'gallery');

        return $data;
    }
}
