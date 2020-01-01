<?php
namespace SK\GalleryModule\Admin\Controller;

use Yii;
use yii\base\Event;
use yii\helpers\Url;
use yii\db\Expression;
use yii\web\Controller;
use yii\base\DynamicModel;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;
use RS\Component\User\Model\User;

use yii\web\NotFoundHttpException;
use SK\GalleryModule\Model\Image;
use SK\GalleryModule\Model\Gallery;
use SK\GalleryModule\Model\Category;
use League\Flysystem\FilesystemInterface;
use SK\GalleryModule\Model\RotationStats;
use RS\Component\Core\Settings\SettingsInterface;
use SK\GalleryModule\Queue\CreateThumbsJob;
use SK\GalleryModule\Admin\Form\GalleryForm;
use RS\Component\Core\Generator\TimeIntervalGenerator;
use SK\GalleryModule\Admin\Form\GalleryFilterForm;
use SK\GalleryModule\Service\Image as ImageService;
use SK\GalleryModule\EventSubscriber\GallerySubscriber;
use SK\GalleryModule\Service\Gallery as GalleryService;
use SK\GalleryModule\Admin\Form\GalleriesBatchActionsForm;

/**
 * MainController implements the CRUD actions for Gallery model.
 */
class MainController extends Controller
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
                    'batch-delete' => ['post'],
                    'delete-image' => ['post'],
                    'set-cover-image' => ['post'],
                    'image-toggle-enabled' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @param \yii\base\Action $action
     *
     * @return bool
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        if (in_array($action->id, ['index'], true)) {
            Url::remember('', 'actions-redirect');
        }

        //Event::on(Gallery::class, Gallery::EVENT_BEFORE_INSERT, [GallerySubscriber::class, 'onCreate']);
        //Event::on(Gallery::class, Gallery::EVENT_BEFORE_UPDATE, [GallerySubscriber::class, 'onUpdate']);
        //Event::on(Gallery::class, Gallery::EVENT_BEFORE_DELETE, [GallerySubscriber::class, 'onDelete']);

        return parent::beforeAction($action);
    }

    /**
     * Lists all Galleries models.
     * @return mixed
     */
    public function actionIndex(int $page = 1)
    {
        $filerForm = new GalleryFilterForm();

        $dataProvider = $filerForm->search(Yii::$app->request->get());
        $dataProvider->prepare(true);

        $categoriesNames = Category::find()
            ->select('title')
            ->indexBy('category_id')
            ->column();

        $userNames = User::find()
            ->select('username')
            ->indexBy('user_id')
            ->column();

        $statusNames = Gallery::getStatusNames();

        return $this->render('index', [
            'page' => $page,
            'filterForm' => $filerForm,
            'dataProvider' => $dataProvider,
            'categoriesNames' => $categoriesNames,
            'userNames' => $userNames,
            'statusNames' => $statusNames,
        ]);
    }

    /**
     * Displays a single Gallery model.
     *
     * @param integer $id
     *
     * @return mixed
     */
    public function actionView(int $id)
    {
        $gallery = $this->findById($id);

        $rotationStats = RotationStats::find()
            ->with('image')
            ->with('category')
            ->where(['gallery_id' => $gallery->getId()])
            ->orderBy(['ctr' => SORT_DESC])
            ->all();

        $thumbsRotationStats = [];

        foreach ($rotationStats as $item) {
            if (empty($thumbsRotationStats[$item->image->getId()]['image'])) {
                $thumbsRotationStats[$item->image->getId()]['image'] = $item->image;
            }

            $thumbsRotationStats[$item->image->getId()]['categories'][] = $item;
        }

        $statusNames = Gallery::getStatusNames();

        return $this->render('view', [
            'gallery' => $gallery,
            'statusNames' => $statusNames,
            'thumbsRotationStats' => $thumbsRotationStats,
        ]);
    }

    /**
     * Creates a new Gallery model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return mixed
     */
    public function actionCreate()
    {
        $settings = Yii::$container->get(SettingsInterface::class);
        $gallery = new Gallery;

        $form = new GalleryForm([
            'categories_ids' => ArrayHelper::getColumn($gallery->categories, 'category_id'),
        ]);

        if ($form->load(Yii::$app->request->post()) && $form->isValid()) {
            $currentDatetime = gmdate('Y-m-d H:i:s');

            $gallery->setAttributes($form->getAttributes());
            $gallery->generateSlug($form->slug);
            $gallery->updated_at = $currentDatetime;
            $gallery->created_at = $currentDatetime;

            if ('dont-set' === $form->published_at_method) {
                $gallery->published_at = null;
            } elseif ('interval' === $form->published_at_method) {
                $maxDate = Gallery::find()
                    ->where(['>=', 'published_at', new Expression('NOW() - INTERVAL 1 DAY')])
                    ->onlyActive()
                    ->max('published_at');
                if (null === $maxDate) {
                    $maxDate = gmdate('Y-m-d H:i:s');
                }


                $timespan = $settings->get('autoposting_fixed_interval', 8640, 'gallery');
                $dispersion = $settings->get('autoposting_fixed_interval', 600, 'gallery');
                $startDate = new \DateTime($maxDate);
                $interval = new \DateInterval("PT{$timespan}S");

                $timeIntervalGenerator = new TimeIntervalGenerator($startDate, $interval, $dispersion);
                $gallery->published_at = $timeIntervalGenerator->next()->format('Y-m-d H:i:s');
            }

            if ($gallery->save()) {
                $galleryService = Yii::$container->get(GalleryService::class);
                $galleryService->updateCategoriesByIds($gallery, $form->categories_ids);

                $pathGenerator = new \RS\Component\Core\Generator\FilepathGenerator;
                $filesystem = Yii::$container->get(FilesystemInterface::class);

                foreach ($form->images as $i => $uploadedImage) {
                    if ($uploadedImage->error !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $file = new \SplFileInfo($uploadedImage->tempName);
                    $fileHash = md5_file($uploadedImage->tempName);
                    $path = $pathGenerator->generateByHash($file);
                    // расширение файла. у темп файла его нету
                    $path_parts = pathinfo($uploadedImage->name);
                    $extension = strtolower($path_parts['extension']);
                    $path .= $extension;
                    // В конфиге модуля галереи положить сторадж со своим конфигом.
                    try {
                        $filesystem->write("photos/src{$path}", file_get_contents($uploadedImage->tempName));

                        $image = new Image();
                        $image->setPath($path);
                        $image->setOrdering($i);
                        $image->enable();
                        $image->setHash($fileHash);
                        $image->setCreatedAt(gmdate('Y-m-d H:i:s'));

                        if ($image->save()) {
                            $gallery->addImage($image);

                            if (0 === $i) { // Первое фото при создании станет обложкой фотосета
                                $gallery->setCoverImage($image);
                            }

                            Yii::$app->queue
                                ->push(new CreateThumbsJob([
                                    'image_id' => $image->getId(),
                                ]));
                        }

                        @unlink($uploadedImage->tempName);
                    } catch(\League\Flysystem\FileExistsException $e) {
                        Yii::warning($e->getMessage());
                    }
                }

                // Добавим фотки в таблицу ротации
                foreach($gallery->categories as $category) {
                    foreach($gallery->images as $image) {
                        RotationStats::addGallery($category, $gallery, $image);
                    }
                }

                Yii::$app->session->addFlash('success', 'Новая галерея добавлена');
            }

            return $this->redirect(['index']);
        }

        $categoriesNames = Category::find()
            ->select('title')
            ->indexBy('category_id')
            ->column();

        $userNames = User::find()
            ->select('username')
            ->indexBy('user_id')
            ->column();

        $statusNames = Gallery::getStatusNames();

        return $this->render('create', [
            'gallery' => $gallery,
            'form' => $form,
            'categoriesNames' => $categoriesNames,
            'userNames' => $userNames,
            'statusNames' => $statusNames,
        ]);
    }

    /**
     * Updates an existing Gallery model.
     *
     * @param integer $id
     *
     * @return mixed
     */
    public function actionUpdate(int $id)
    {
        $settings = Yii::$container->get(SettingsInterface::class);
        $gallery = $this->findById($id);

        $oldCategoriesIds = ArrayHelper::getColumn($gallery->categories, 'category_id');

        $form = new GalleryForm([
            'categories_ids' => $oldCategoriesIds,
        ]);
        $form->setAttributes($gallery->getAttributes());

        if ($form->load(Yii::$app->request->post()) && $form->isValid()) {
            $currentDatetime = gmdate('Y-m-d H:i:s');
            $oldPublishedAt = $gallery->published_at;

            $gallery->setAttributes($form->getAttributes());
            $gallery->generateSlug($form->slug);
            $gallery->updated_at = $currentDatetime;

            if ('dont-set' === $form->published_at_method) {
                $gallery->published_at = $oldPublishedAt;
            } elseif ('interval' === $form->published_at_method) {
                $maxDate = Gallery::find()
                    ->where(['>=', 'published_at', new Expression('NOW() - INTERVAL 1 DAY')])
                    ->onlyActive()
                    ->max('published_at');
                if (null === $maxDate) {
                    $maxDate = gmdate('Y-m-d H:i:s');
                }


                $timespan = $settings->get('autoposting_fixed_interval', 8640, 'gallery');
                $dispersion = $settings->get('autoposting_fixed_interval', 600, 'gallery');
                $startDate = new \DateTime($maxDate);
                $interval = new \DateInterval("PT{$timespan}S");

                $timeIntervalGenerator = new TimeIntervalGenerator($startDate, $interval, $dispersion);
                $gallery->published_at = $timeIntervalGenerator->next()->format('Y-m-d H:i:s');
            }

            if ($gallery->save()) {
                $newCategoriesIds = $form->categories_ids;

                $galleryService = Yii::$container->get(GalleryService::class);
                $galleryService->updateCategoriesByIds($gallery, $form->categories_ids);

                $maxOrdering = Image::find()
                    ->where(['gallery_id' => $gallery->getId()])
                    ->max('ordering');

                $pathGenerator = new \RS\Component\Core\Generator\FilepathGenerator;
                $filesystem = Yii::$container->get(FilesystemInterface::class);

                foreach ($form->images as $i => $uploadedImage) {
                    if ($uploadedImage->error !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $file = new \SplFileInfo($uploadedImage->tempName);
                    $fileHash = md5_file($uploadedImage->tempName);
                    $path = $pathGenerator->generateByHash($file);
                    // расширение файла. у темп файла его нету$maxOrdering + 1
                    $path_parts = pathinfo($uploadedImage->name);
                    $extension = strtolower($path_parts['extension']);
                    $path .= $extension;
// В конфиге модуля галереи положить сторадж со своим конфигом.
                    try {
                        $filesystem->write("photos/src{$path}", file_get_contents($uploadedImage->tempName));

                        $image = new Image();
                        $image->setPath($path);
                        $image->setOrdering($maxOrdering + 1);
                        $image->enable();
                        $image->setHash($fileHash);
                        $image->setCreatedAt(gmdate('Y-m-d H:i:s'));

                        if ($image->save()) {
                            $gallery->addImage($image);
                            $maxOrdering++;

                            Yii::$app->queue
                                ->push(new CreateThumbsJob([
                                    'image_id' => $image->getId(),
                                ]));
                        }
                    } catch(\League\Flysystem\FileExistsException $e) {
                        Yii::warning($e->getMessage());
                    }
                }

                // Добавим фотки в таблицу ротации
                foreach($gallery->categories as $category) {
                    foreach($gallery->images as $image) {
                        RotationStats::addGallery($category, $gallery, $image);
                    }
                }

                Yii::$app->session->addFlash('success', 'Gallery updated: ' . $gallery->getTitle());
            }

            return $this->redirect(Url::previous('actions-redirect'));
        }

        $categoriesNames = Category::find()
            ->select('title')
            ->indexBy('category_id')
            ->column();

        $userNames = User::find()
            ->select('username')
            ->indexBy('user_id')
            ->column();

        $statusNames = Gallery::getStatusNames();

        return $this->render('update', [
            'gallery' => $gallery,
            'form' => $form,
            'categoriesNames' => $categoriesNames,
            'userNames' => $userNames,
            'statusNames' => $statusNames,
        ]);
    }

    /**
     * Deletes an existing Gallery model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param integer $id
     *
     * @return mixed
     */
    public function actionDelete(int $id)
    {
        $gallery = $this->findById($id);

        $title = $gallery->getTitle();

        $galleryService = Yii::$container->get(GalleryService::class);

        if ($galleryService->delete($gallery)) {
            Yii::$app->session->addFlash('success', "Gallery removed: {$title}");
        }

        return $this->redirect(['index']);
    }

    /**
     * Массовое удаление галерей (по галочку)
     */
    public function actionBatchDelete()
    {
        $ajaxForm = new DynamicModel(['galleries_ids']);

        $ajaxForm->addRule('galleries_ids', 'each', ['rule' => ['integer']]);
        $ajaxForm->addRule('galleries_ids', 'filter', ['filter' => 'array_filter']);
        $ajaxForm->addRule('galleries_ids', 'required', ['message' => 'Galleries not select']);

        $ajaxForm->load(Yii::$app->request->post(), '');
        // Валидация массива идентификаторов видео.
        if (!$ajaxForm->validate()) {
            return $this->asJson([
                'error' => [
                    'message' => Yii::t('galleries', 'Validation failure'),
                ]
            ]);
        }

        $galleriesQuery = Gallery::find()
            ->where(['gallery_id' => $ajaxForm->galleries_ids]);

        $galleryService = Yii::$container->get(GalleryService::class);

        $deletedNum = 0;
        foreach ($galleriesQuery->batch(20) as $galleries) {
            foreach ($galleries as $gallery) {
                if ($galleryService->delete($gallery)) {
                    $deletedNum ++;
                }
            }
        }

        return $this->asJson([
            'message' => Yii::t('galleries', '<b>{num}</b> galleries deleted', ['num' => $deletedNum])
        ]);
    }

    /**
     * Массовые действия с галереями
     */
    public function actionBatchActions()
    {
        $form = new GalleriesBatchActionsForm();

        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $form->handle();

                return $this->asJson([
                    'message' => 'Success'
                ]);
            } catch (\Exception $e) {

                return $this->asJson([
                    'error' => [
                        'message' => $e->getMessage(),
                    ]
                ]);
            }
        }

        $statusNames = Gallery::getStatusNames();
        $userNames = User::find()
			->select( 'username')
			->indexBy('user_id')
			->column();
        $categoryNames = Category::find()
			->select('title')
			->indexBy('category_id')
			->column();

        return $this->renderPartial('batch-actions', [
            'form' => $form,
            'statusNames' => $statusNames,
            'userNames' => $userNames,
            'categoryNames' => $categoryNames,
        ]);
    }

    /**
     * Установка главной тумбы у поста
     */
    public function actionSetCoverImage()
    {
        $galleryId = (int) Yii::$app->request->post('gallery_id', 0);
        $imageId = (int) Yii::$app->request->post('image_id', 0);

        $gallery = Gallery::findOne($galleryId);
        $image = Image::findOne([
            'gallery_id' => (null !== $gallery) ? $gallery->getId() : 0,
            'image_id' => $imageId
        ]);

        if (null === $gallery || null === $image) {
            return $this->asJson([
                'error' => [
                    'code' => 404,
                    'message' => 'Unable find gallery or image',
                ]
            ]);
        }

        $gallery->setCoverImage($image);

        return $this->asJson(['message' => 'New cover image submited']);
    }

    /**
     * Удаление картинки.
     */
    public function actionDeleteImage()
    {
        $galleryId = (int) Yii::$app->request->post('gallery_id', 0);
        $imageId = (int) Yii::$app->request->post('image_id', 0);

        $gallery = Gallery::findOne($galleryId);
        $image = Image::findOne([
            'gallery_id' => (null !== $gallery) ? $gallery->getId() : 0,
            'image_id' => $imageId
        ]);

        if (null === $gallery || null === $image) {
            return $this->asJson([
                'error' => [
                    'code' => 404,
                    'message' => 'Unable find gallery or image',
                ]
            ]);
        }

        $imageService = Yii::$container->get(ImageService::class);
        $imageService->delete($image);

        if ($gallery->image_id === $image->getId()) {
            $gallery->image_id = 0;
            $gallery->images_num -= 1;
            $gallery->save();
        }

        return $this->asJson(['message' => 'Image deleted']);
    }

    /**
     * Включение\выключение картинки.
     */
    public function actionImageToggleEnabled()
    {
        $galleryId = (int) Yii::$app->request->post('gallery_id', 0);
        $imageId = (int) Yii::$app->request->post('image_id', 0);

        $gallery = Gallery::findOne($galleryId);
        $image = Image::findOne([
            'gallery_id' => (null !== $gallery) ? $gallery->getId() : 0,
            'image_id' => $imageId
        ]);

        if (null === $gallery || null === $image) {
            return $this->asJson([
                'error' => [
                    'code' => 404,
                    'message' => 'Unable find gallery or image',
                ]
            ]);
        }

        $image->setEnabled(!$image->isEnabled());
        $image->save();

        if ($image->isEnabled()) {
            return $this->asJson(['message' => 'Enable image', 'enabled' => true]);
        } else {
            return $this->asJson(['message' => 'Disable image', 'enabled' => false]);
        }
    }

    /**
     * Сохраняет порядок сортировки изображений, установленный пользователем.
     *
     * @param integer $id Идентификатор галереи
     * @return array
     */
    public function actionSaveOrder(int $id)
    {
        $gallery = Gallery::findOne($id);

        if (null === $gallery) {
            return [
                'error' => [
                    'code' => 404,
                    'message' => $e->getMessage(),
                ],
            ];
        }

            // Валидация массива идентификаторов изображений.
        $validationModel = DynamicModel::validateData(['image_ids' => Yii::$app->request->post('order')], [
            ['image_ids', 'each', 'rule' => ['integer']],
            ['image_ids', 'filter', 'filter' => 'array_filter'],
            ['image_ids', 'required', 'message' => 'Images not selected'],
        ]);

        if ($validationModel->hasErrors()) {
            return $this->asJson([
                'error' => [
                    'code' => 0,
                    'message' => implode('<br>', $validationModel->getErrorSummary(true)),
                ],
            ]);
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            $image_ids_list = implode(',', $validationModel->image_ids);

            $sql = "UPDATE `" . Image::tableName() . "`
                    SET `ordering` = FIND_IN_SET(`image_id`, '{$image_ids_list}')
                    WHERE FIND_IN_SET(`image_id`, '{$image_ids_list}') != 0";

            $db->createCommand($sql)->execute();
            $transaction->commit();

            return $this->asJson([
                'message' => 'Порядок сортировки изображений сохранен'
            ]);
        } catch (\Throwable $e) {
            $transaction->rollBack();

            return $this->asJson([
                'error' => [
                    'message' => $e->getMessage(),
                ],
            ]);
        }
    }

    /**
     * Finds the Gallery model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Gallery the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findById(int $id)
    {
        $gallery = Gallery::find()
            ->with('coverImage', 'categories', 'images')
            ->where(['gallery_id' => $id])
            ->one();

        if (null === $gallery) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $gallery;
    }

    /**
     * Возвращает список статусов галереи
     *
     * @return array
     */
    protected function getStatusNames()
    {
        return [
    		Gallery::STATUS_DISABLED => Yii::t('gallery', 'status_disabled'),
    		Gallery::STATUS_ACTIVE => Yii::t('gallery', 'status_active'),
    		Gallery::STATUS_MODERATE => Yii::t('gallery', 'status_moderate'),
    		Gallery::STATUS_DELETED => Yii::t('gallery', 'status_deleted'),
    	];
    }
}
