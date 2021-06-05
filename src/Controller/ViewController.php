<?php

namespace SK\GalleryModule\Controller;

use RS\Component\Core\Filter\QueryParamsFilter;
use RS\Component\Core\Settings\SettingsInterface;
use SK\GalleryModule\Cache\PageCache;
use SK\GalleryModule\EventSubscriber\GallerySubscriber;
use SK\GalleryModule\Model\Gallery;
use Yii;
use yii\base\ViewContextInterface;
use yii\caching\DbDependency;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * ViewController implements the view action for Gallery model.
 */
class ViewController extends Controller implements ViewContextInterface
{
    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            'queryParams' => [
                'class' => QueryParamsFilter::class,
                'actions' => [
                    'index' => ['id', 'slug'],
                ],
            ],
            'pageCache' => [
                'class' => PageCache::class,
                'enabled' => (bool) Yii::$container->get(SettingsInterface::class)->get('enable_page_cache', false),
                'only' => ['index'],
                'duration' => 3600,
                'dependency' => [
                    'class' => DbDependency::class,
                    'sql' => 'SELECT 1',
                ],
                'variations' => [
                    Yii::$app->language,
                    Yii::$app->request->get('id', 1),
                    Yii::$app->request->get('slug', 1),
                ],
            ],
        ];
    }

    /**
     * Переопределяет дефолтный путь шаблонов модуля.
     * Путь задается в конфиге модуля, в компонентах приложения.
     *
     * @return string
     */
    public function getViewPath(): string
    {
        return $this->module->getViewPath();
    }

    /**
     * Показывает страницу просмотра видео.
     *
     * @param integer $id
     * @param string $slug
     * @return mixed
     */
    public function actionIndex($id = 0, $slug = '')
    {
        $settings = Yii::$container->get(SettingsInterface::class);

        if (0 !== (int) $id) {
            $gallery = $this->findById($id);
        } elseif (!empty($slug)) {
            $gallery = $this->findBySlug($slug);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $template = $gallery->getTemplate() ?: 'view';

        if ($settings->get('internal_register_activity', true, 'gallery')) {
            $this->on(self::EVENT_AFTER_ACTION, [GallerySubscriber::class, 'onView'], $gallery);
        }

        return $this->render($template, [
            'settings' => $settings,
            'gallery' => $gallery,
        ]);
    }

    /**
     * Find gallery by slug
     *
     * @param string $slug
     * @return null|Gallery
     * @throws NotFoundHttpException
     */
    protected function findBySlug($slug)
    {
        $gallery = Gallery::find()
            ->with([
                'coverImage' => function ($query) {
                    $query->select(['image_id', 'gallery_id', 'path', 'source_url'])
                        ->where(['enabled' => 1]);
                }
            ])
            ->with([
                'images' => function ($query) {
                    $query->select(['image_id', 'gallery_id', 'path', 'source_url'])
                        ->where(['enabled' => 1]);
                }
            ])
            ->with([
                'categories' => function ($query) {
                    $query->select(['category_id', 'title', 'slug', 'h1'])
                        ->where(['enabled' => 1]);
                }
            ])
            ->where(['slug' => $slug])
            ->untilNow()
            ->onlyActive()
            ->one();

        if (null === $gallery) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $gallery;
    }

    /**
     * Find gallery by id
     *
     * @param integer $id
     * @return null|Gallery
     * @throws NotFoundHttpException
     */
    protected function findById($id)
    {
        $gallery = Gallery::find()
            ->with([
                'coverImage' => function ($query) {
                    $query->select(['image_id', 'gallery_id', 'path', 'source_url'])
                        ->where(['enabled' => 1]);
                }
            ])
            ->with([
                'images' => function ($query) {
                    $query->select(['image_id', 'gallery_id', 'path', 'source_url'])
                        ->where(['enabled' => 1]);
                }
            ])
            ->with([
                'categories' => function ($query) {
                    $query->select(['category_id', 'title', 'slug', 'h1'])
                        ->where(['enabled' => 1]);
                }
            ])
            ->where(['gallery_id' => $id])
            ->untilNow()
            ->onlyActive()
            ->one();

        if (null === $gallery) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $gallery;
    }
}
