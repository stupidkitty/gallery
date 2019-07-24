<?php
namespace SK\GalleryModule\Admin\Controller;

use Yii;
use yii\base\Event;
use yii\web\Controller;
use yii\base\DynamicModel;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use SK\GalleryModule\Model\Category;
use SK\GalleryModule\Admin\Form\CategoryForm;
use SK\GalleryModule\EventSubscriber\CategorySubscriber;

/**
 * CategoriesController implements the CRUD actions for Category model.
 */
class CategoriesController extends Controller
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
                    'save-order' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (in_array($action->id, ['save-order'])) {
            $this->enableCsrfValidation = false;
        }

        Event::on(Category::class, Category::EVENT_BEFORE_INSERT, [CategorySubscriber::class, 'onCreate']);
        Event::on(Category::class, Category::EVENT_BEFORE_UPDATE, [CategorySubscriber::class, 'onUpdate']);
        Event::on(Category::class, Category::EVENT_BEFORE_DELETE, [CategorySubscriber::class, 'onDelete']);

        return parent::beforeAction($action);
    }

    /**
     * Lists all Category models.
     *
     * @return mixed
     */
    /*public function actionIndex()
    {
        $categories = Category::find()
            ->orderBy(['ordering' => SORT_ASC])
            ->all();

        $form = new CategoryForm();

        return $this->render('index', [
            'categories' => $categories,
            'form' => $form,
        ]);
    }*/

    /**
     * Displays a single Category model.
     *
     * @param integer $id
     *
     * @return mixed
     */
    public function actionView($id)
    {
        $categories = Category::find()
            ->orderBy(['ordering' => SORT_ASC])
            ->all();


        $category = $this->findById($id);

        $sql = "
            SELECT DISTINCT `z`.`category_id`, `z`.`date`, ROUND(((`z`.`category_clicks` / IFNULL(`z`.`total_clicks`, 1)) * 100), 2) AS `popularity`
            FROM (
            	SELECT `category_id`, `date`,
                    SUM(`clicks`) OVER (PARTITION BY `category_id`, `date`) AS `category_clicks`,
                    SUM(`clicks`) OVER (PARTITION BY `date`) AS `total_clicks`
                FROM `galleries_categories_stats`
            ) AS `z`
            WHERE `z`.`category_id` = :category_id
            ORDER BY `z`.`date` DESC
        ";
        $popularityStats = Yii::$app->db->createCommand($sql)
            ->bindValue(':category_id', $category->getId())
            ->queryAll();

        $popularityStats = \yii\helpers\ArrayHelper::index($popularityStats, 'date');

        $currentDate = new \DateTime('now', new \DateTimeZone('utc'));
        $stats = [];

        for ($i = 0; $i < 30; $i ++) {
            $currentDay = $currentDate->format('Y-m-d');

            $popularity = $popularityStats[$currentDay]['popularity'] ?? 0;

            $stats[] = [
                'date' => $currentDay,
                'popularity' => $popularity,
            ];

            $currentDate->sub(new \DateInterval('P1D'));
        }
        $stats = array_reverse($stats);

        return $this->render('view', [
            'category' => $category,
            'categories' => $categories,
            'stats' => $stats,
        ]);
    }

    /**
     * Create new Category model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param integer $id
     *
     * @return mixed
     */
    public function actionCreate()
    {
        $form = new CategoryForm;

        if ($form->load(Yii::$app->request->post()) && $form->isValid()) {
            $category = new Category;
            $category->setAttributes($form->getAttributes());
            $category->generateSlug($form->slug);

            if ($category->save()) {
                Yii::$app->session->setFlash('success', Yii::t('gallery', 'Категория "<b>{title}</b>" создана', ['title' => $category->getTitle()]));

                $this->redirect(['create']);
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка сохранения');
            }
        }

        $categories = Category::find()
            ->orderBy(['ordering' => SORT_ASC])
            ->all();

        return $this->render('create', [
            'form' => $form,
            'categories' => $categories,
        ]);
    }

    /**
     * Updates an existing Category model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param integer $id
     *
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $category = $this->findById($id);

        $form = new CategoryForm;
        $form->setAttributes($category->getAttributes());

        if ($form->load(Yii::$app->request->post()) && $form->isValid()) {
            $category->setAttributes($form->getAttributes());
            $category->generateSlug($form->slug);

            if ($category->save()) {
                Yii::$app->session->setFlash('success', 'Новые данные для категории сохранены');

                $this->redirect(['update', 'id' => $id]);
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка сохранения');
            }
        }

        $categories = Category::find()
            ->orderBy(['ordering' => SORT_ASC])
            ->all();

        return $this->render('update', [
            'category' => $category,
            'form' => $form,
            'categories' => $categories,
        ]);
    }

    /**
     * Deletes an existing Category model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param integer $id
     *
     * @return mixed
     */
    public function actionDelete($id)
    {
        $category = $this->findById($id);

        $title = $category->getTitle();

        if ($category->delete()) {
            Yii::$app->session->setFlash('success', Yii::t('galleries', 'Категория "<b>{$title}</b>" успешно удалена', ['title' => $title]));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('galleries', 'Удалить категорию "<b>{$title}</b>" не удалось', ['title' => $title]));
        }

        return $this->redirect(['create']);
    }

    /**
     * Сохраняет порядок сортировки категорий, установленный пользователем.
     * @return mixed
     */
    public function actionSaveOrder()
    {
            // Валидация массива идентификаторов категорий.
        $validationModel = DynamicModel::validateData(['categories_ids' => Yii::$app->request->post('order')], [
            ['categories_ids', 'each', 'rule' => ['integer']],
            ['categories_ids', 'filter', 'filter' => 'array_filter'],
            ['categories_ids', 'required', 'message' => 'Categories not select'],
        ]);

        if ($validationModel->hasErrors()) {
            return $this->asJson([
                'error' => [
                    'code' => 1,
                    'message' => 'Validation fail',
                ],
            ]);
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            Category::updateAll([
                '{{ordering}}' => new \yii\db\Expression("FIND_IN_SET(`category_id`, :id_list)"),
            ], [
                '!=', new \yii\db\Expression("FIND_IN_SET(`category_id`, :id_list)"), 0,
            ], [
                ':id_list' => \implode(',', $validationModel->categories_ids),
            ]);

            $transaction->commit();

            return $this->asJson([
                'message' => 'Порядок сортировки категорий сохранен'
            ]);
        } catch (\Exception $e) {
            $transaction->rollBack();

            return $this->asJson([
                'error' => [
                    'code' => 2,
                    'message' => $e->getMessage(),
                ],
            ]);
        }
    }

    /**
     * Поиск категории по ее идентификатору
     *
     * @param integer $id Идентификатор категории
     *
     * @return mixed
     *
     * @throw NotFoundHttpException Если категория не найдена.
     */
    protected function findById(int $id)
    {
        $category = Category::find()
            ->with('coverImage')
            ->where(['category_id' => $id])
            ->one();

        if (null === $category) {
            throw new NotFoundHttpException('The requested category does not exist.');
        }

        return $category;
    }
}
