<?php
namespace SK\GalleryModule\Controller;

use Yii;
use yii\data\Sort;
use yii\web\Controller;
use yii\filters\PageCache;
use yii\filters\VerbFilter;
use yii\caching\DbDependency;
use yii\data\ActiveDataProvider;
use yii\base\ViewContextInterface;
use yii\web\NotFoundHttpException;
use SK\GalleryModule\Model\Gallery;
use SK\GalleryModule\Model\Category;
use RS\Component\Core\Filter\QueryParamsFilter;
use RS\Component\Core\Settings\SettingsInterface;
use SK\GalleryModule\Model\GalleriesCategoriesMap;
use SK\GalleryModule\Provider\RotateGalleryProvider;
use SK\GalleryModule\EventSubscriber\GallerySubscriber;

/**
 * CategoryController implements the list actions for Category model.
 */
class CategoryController extends Controller implements ViewContextInterface
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            /*'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'click' => ['post'],
                ],
            ],*/
            'queryParams' => [
                'class' => QueryParamsFilter::class,
                'actions' => [
                    'index' => ['id', 'slug', 'page', 'o', 't'],
                    'date' => ['id', 'slug', 'page', 't'],
                    'views' => ['id', 'slug', 'page', 't'],
                    'likes' => ['id', 'slug', 'page', 't'],
                    'ctr' => ['id', 'slug', 'page', 't'],
                    'list-all' => ['sort'],
                ],
            ],
            'pageCache' => [
                'class' => PageCache::class,
                'enabled' => (bool) Yii::$container->get(SettingsInterface::class)->get('enable_page_cache', false),
                'only' => ['index', 'ctr', 'list-all'],
                'duration' => 600,
                'dependency' => [
                    'class' => DbDependency::class,
                    'sql' => 'SELECT MAX(`published_at`) FROM `galleries` WHERE `published_at` <= NOW()',
                ],
                'variations' => [
                    Yii::$app->language,
                    $this->action->id,
                    Yii::$app->request->get('id', 0),
                    Yii::$app->request->get('slug', ''),
                    Yii::$app->request->get('page', 1),
                    Yii::$app->request->get('o', ''),
                    Yii::$app->request->get('t', 'all-time'),
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
    public function getViewPath()
    {
        return $this->module->getViewPath();
    }

    /**
     * Заметка.Можно считать клики в категорию по входу в первую страницу,
     * но в таком случае придется делать запрос на поиск по слагу.
     * Попробовать решить этот момент.
     */

    /**
     * Показывает список видео роликов текущей категории.
     *
     * @return mixed
     */
    public function actionIndex(int $id = 0, string $slug = '', int $page = 1, string $o = 'date', string $t = 'all-time')
    {
        $settings = Yii::$container->get(SettingsInterface::class);

        if (0 !== $id) {
            $category = $this->findById($id);
        } elseif (!empty($slug)) {
            $category = $this->findBySlug($slug);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if ('ctr' === $o) {
            $dataProvider = new RotateGalleryProvider([
                'pagination' => [
                    'defaultPageSize' => $settings->get('items_per_page', 24, 'gallery'),
                    'pageSize' => $settings->get('items_per_page', 24, 'gallery'),
                    'forcePageParam' => false,
                ],
                'sort' => $this->buildSort(),
                'category_id' => $category['category_id'],
                'testPerPagePercent' => (int) $settings->get('test_items_percent', 15, 'gallery'),
                'testGalleriesStartPosition' => (int) $settings->get('test_items_start', 3, 'gallery'),
                'datetimeLimit' => $t,
            ]);
        } else {
            $query = $this->buildInitialQuery($category, $t);

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'totalCount' => $query->cachedCount(),
                'pagination' => [
                    'defaultPageSize' => $settings->get('items_per_page', 24, 'gallery'),
                    'pageSize' => $settings->get('items_per_page', 24, 'gallery'),
                    'forcePageParam' => false,
                ],
                'sort' => $this->buildSort(),
            ]);
        }

        $galleries = $dataProvider->getModels();
        $pagination = $dataProvider->getPagination();

        if ($settings->get('internal_register_activity', true, 'gallery')) {
            $this->on(
                self::EVENT_AFTER_ACTION,
                [
                    GallerySubscriber::class,
                    'onShowCategoryThumbs'
                ],
                [
                    'category_id' => $category['category_id'],
                    'images_ids' => array_column($galleries, 'image_id'),
                    'page' => $page,
                ]
            );
        }

        return $this->render('category_galleries', [
            'page' => $page,
            'sort' => $o,
            'settings' => $settings,
            'category' => $category,
            'galleries' => $galleries,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Показывает список видео роликов текущей категории осортированных по дате добавления.
     *
     * @return mixed
     */
    public function actionDate(int $id = 0, string $slug = '', int $page = 1, string $t = 'all-time')
    {
        $settings = Yii::$container->get(SettingsInterface::class);

        if (0 !== $id) {
            $category = $this->findById($id);
        } elseif (!empty($slug)) {
            $category = $this->findBySlug($slug);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $query = $this->buildInitialQuery($category, $t);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'totalCount' => $query->cachedCount(),
            'pagination' => [
                'defaultPageSize' => $settings->get('items_per_page', 24, 'gallery'),
                'pageSize' => $settings->get('items_per_page', 24, 'gallery'),
                'forcePageParam' => false,
            ],
            'sort' => [
                'defaultOrder' => [
                    'published_at' => SORT_DESC,
                ]
            ],
        ]);

        $galleries = $dataProvider->getModels();
        $pagination = $dataProvider->getPagination();

        if ($settings->get('internal_register_activity', true, 'gallery')) {
            $this->on(
                self::EVENT_AFTER_ACTION,
                [
                    GallerySubscriber::class,
                    'onShowCategoryThumbs'
                ],
                [
                    'category_id' => $category->getId(),
                    'images_ids' => array_column($galleries, 'image_id'),
                    'page' => $page,
                ]
            );
        }

        return $this->render('category_galleries', [
            'page' => $page,
            'sort' => $this->action->id,
            'settings' => $settings,
            'category' => $category,
            'galleries' => $galleries,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Показывает список видео роликов текущей категории осортированных по просмортрам.
     *
     * @return mixed
     */
    public function actionViews(int $id = 0, string $slug = '', int $page = 1, string $t = 'all-time')
    {
        $settings = Yii::$container->get(SettingsInterface::class);

        if (0 !== $id) {
            $category = $this->findById($id);
        } elseif (!empty($slug)) {
            $category = $this->findBySlug($slug);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $query = $this->buildInitialQuery($category, $t);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'totalCount' => $query->cachedCount(),
            'pagination' => [
                'defaultPageSize' => $settings->get('items_per_page', 24, 'galleries'),
                'pageSize' => $settings->get('items_per_page', 24, 'galleries'),
                'forcePageParam' => false,
            ],
            'sort' => [
                'defaultOrder' => [
                    'views' => SORT_DESC,
                ]
            ],
        ]);

        $galleries = $dataProvider->getModels();
        $pagination = $dataProvider->getPagination();

        if ($settings->get('internal_register_activity', true, 'galleries')) {
            $this->on(
                self::EVENT_AFTER_ACTION,
                [
                    GallerySubscriber::class,
                    'onShowCategoryThumbs'
                ],
                [
                    'category_id' => $category->getId(),
                    'images_ids' => array_column($galleries, 'image_id'),
                    'page' => $page,
                ]
            );
        }

        return $this->render('category_galleries', [
            'page' => $page,
            'sort' => $this->action->id,
            'settings' => $settings,
            'category' => $category,
            'galleries' => $galleries,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Показывает список видео роликов текущей категории осортированных по лайкам.
     *
     * @return mixed
     */
    public function actionLikes(int $id = 0, string $slug = '', int $page = 1, string $t = 'all-time')
    {
        $settings = Yii::$container->get(SettingsInterface::class);

        if (0 !== $id) {
            $category = $this->findById($id);
        } elseif (!empty($slug)) {
            $category = $this->findBySlug($slug);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $query = $this->buildInitialQuery($category, $t);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'totalCount' => $query->cachedCount(),
            'pagination' => [
                'defaultPageSize' => $settings->get('items_per_page', 24, 'gallery'),
                'pageSize' => $settings->get('items_per_page', 24, 'gallery'),
                'forcePageParam' => false,
            ],
            'sort' => [
                'defaultOrder' => [
                    'likes' => SORT_DESC,
                ]
            ],
        ]);

        $galleries = $dataProvider->getModels();
        $pagination = $dataProvider->getPagination();

        if ($settings->get('internal_register_activity', true, 'gallery')) {
            $this->on(
                self::EVENT_AFTER_ACTION,
                [
                    GallerySubscriber::class,
                    'onShowCategoryThumbs'
                ],
                [
                    'category_id' => $category->getId(),
                    'images_ids' => array_column($galleries, 'image_id'),
                    'page' => $page,
                ]
            );
        }

        return $this->render('category_galleries', [
            'page' => $page,
            'sort' => $this->action->id,
            'settings' => $settings,
            'category' => $category,
            'galleries' => $galleries,
            'pagination' => $pagination,
        ]);
    }


    /**
     * List galleries in category ordered by ctr
     *
     * @return mixed
     */
    public function actionCtr(int $id = 0, string $slug = '', int $page = 1, string $t = 'all-time')
    {
        $settings = Yii::$container->get(SettingsInterface::class);

        if (0 !== $id) {
            $category = $this->findById($id);
        } elseif (!empty($slug)) {
            $category = $this->findBySlug($slug);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $dataProvider = new RotateGalleryProvider([
            'pagination' => [
                'defaultPageSize' => $settings->get('items_per_page', 24, 'gallery'),
                'pageSize' => $settings->get('items_per_page', 24, 'gallery'),
                'forcePageParam' => false,
            ],
            'sort' => [
                'sortParam' => 'o',
                'attributes' => [
                    'ctr' => [ // top rated
                        'asc' => ['gs.ctr' => SORT_DESC],
                        'desc' => ['gs.ctr' => SORT_DESC],
                        'default' => SORT_DESC,
                    ],
                ],
                'defaultOrder' => [
                    'ctr' => [ // top rated
                        'gs.ctr' => SORT_DESC,
                    ],
                ],
            ],
            'category_id' => $category->getId(),
            'testPerPagePercent' => (int) $settings->get('test_items_percent', 15, 'gallery'),
            'testGalleriesStartPosition' => (int) $settings->get('test_items_start', 3, 'gallery'),
        ]);

        $dataProvider->prepare();

        $galleries = $dataProvider->getModels();
        $pagination = $dataProvider->getPagination();

        if ($settings->get('internal_register_activity', true, 'gallery')) {
            $this->on(
                self::EVENT_AFTER_ACTION,
                [
                    GallerySubscriber::class,
                    'onShowCategoryThumbs'
                ],
                [
                    'category_id' => $category->getId(),
                    'images_ids' => array_column($galleries, 'image_id'),
                    'page' => $page,
                ]
            );
        }

        return $this->render('category_galleries', [
            'page' => $page,
            'sort' => $this->action->id,
            'settings' => $settings,
            'category' => $category,
            'galleries' => $galleries,
            'pagination' => $pagination,
        ]);
    }

    /**
     * List all categories
     *
     * @return mixed
     */
    public function actionListAll(string $sort = '')
    {
        $settings = Yii::$container->get(SettingsInterface::class);

        $sort = new Sort([
            'attributes' => [
                'abc' => [
                    'asc' => ['title' => SORT_ASC],
                    'desc' => ['title' => SORT_ASC],
                    'default' => SORT_ASC,
                ],
                'mv' => [
                    'asc' => ['popularity' => SORT_DESC],
                    'desc' => ['popularity' => SORT_DESC],
                    'default' => SORT_DESC,
                ],
                'vn' => [
                    'asc' => ['galleries_num' => SORT_DESC],
                    'desc' => ['galelries_num' => SORT_DESC],
                    'default' => SORT_DESC,
                ],
            ],
            'defaultOrder' => [
                'mv' => SORT_DESC,
            ],
        ]);

        $categories = Category::find()
            ->select(['category_id', 'slug', 'image_id', 'title', 'description', 'param1', 'param2', 'param3', 'galleries_num'])
            ->with('coverImage')
            ->where(['enabled' => 1])
            ->orderBy($sort->getOrders())
            ->all();

        return $this->render('all_categories', [
            'categories' => $categories,
            'settings' => $settings,
            'sort' => $sort,
        ]);
    }

    /**
     * Find category by slug
     *
     * @param string $slug
     *
     * @return Category
     *
     * @throws NotFoundHttpException
     */
    public function findBySlug($slug)
    {
        $category = Category::find()
            ->where(['slug' => $slug, 'enabled' => 1])
            //->asArray()
            ->one();

        if (null === $category) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $category;
    }

    /**
     * Find category by id
     *
     * @param integer $id
     *
     * @return Category
     *
     * @throws NotFoundHttpException
     */
    public function findById(int  $id)
    {
        $category = Category::find()
            ->where(['category_id' => $id, 'enabled' => 1])
            //->asArray()
            ->one();

        if (null === $category) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $category;
    }

    protected function buildInitialQuery(Category $category, string $t)
    {
        $query = Gallery::find()
            ->select(['g.gallery_id', 'g.image_id', 'g.slug', 'g.title', 'g.orientation', 'g.likes', 'g.dislikes', 'g.images_num', 'g.comments_num', 'g.views', 'g.published_at'])
            ->alias('g')
            ->innerJoin(['gcm' => GalleriesCategoriesMap::tableName()], 'g.gallery_id = gcm.gallery_id')
            ->with(['categories' => function ($query) {
                $query->select(['category_id', 'title', 'slug', 'h1'])
                    ->where(['enabled' => 1]);
            }])
            ->with(['coverImage' => function ($query) {
                $query->select(['image_id', 'gallery_id', 'path', 'source_url'])
                    ->where(['enabled' => 1]);
            }]);

        if ('all-time' === $t) {
            $query->untilNow();
        } elseif ($this->isValidRange($t)) {
            $query->rangedUntilNow($t);
        }

        $query
            ->onlyActive()
            ->andwhere(['gcm.category_id' => $category->getId()]);
            //->asArray();

        return $query;
    }

    protected function buildSort()
    {
        return new Sort([
            'sortParam' => 'o',
            'attributes' => [
                'date' => [
                    'asc' => ['g.published_at' => SORT_DESC],
                    'desc' => ['g.published_at' => SORT_DESC],
                    'default' => SORT_DESC,
                ],
                'mv' => [
                    'asc' => ['g.views' => SORT_DESC],
                    'desc' => ['g.views' => SORT_DESC],
                    'default' => SORT_DESC,
                ],
                'tr' => [
                    'asc' => ['g.likes' => SORT_DESC],
                    'desc' => ['g.likes' => SORT_DESC],
                    'default' => SORT_DESC,
                ],
                'ctr' => [ // top rated
                    'asc' => ['gs.ctr' => SORT_DESC],
                    'desc' => ['gs.ctr' => SORT_DESC],
                    'default' => SORT_DESC,
                ],
            ],
            'defaultOrder' => [
                'date' => [
                    'g.published_at' => SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Проверяет корректность параметра $t в экшене контроллера.
     * Значения: daily, weekly, monthly, early, all_time
     *
     * @param string $time Ограничение по времени.
     *
     * @return string.
     *
     * @throws NotFoundHttpException
     */
    protected function isValidRange($time)
    {
        if (in_array($time, ['daily', 'weekly', 'monthly', 'yearly', 'all-time'])) {
            return true;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
