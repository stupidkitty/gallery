<?php
namespace SK\GalleryModule\Api\Controller;

use Yii;
use yii\base\InvalidConfigException;
use yii\filters\Cors;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\filters\auth\HttpBearerAuth;
use SK\GalleryModule\Model\Gallery;
use SK\GalleryModule\Model\Category;
use SK\GalleryModule\Import\ImageCreator;
use SK\GalleryModule\Api\Form\GalleryForm;
use SK\GalleryModule\Import\GalleryCreator;
use SK\GalleryModule\Service\Gallery as GalleryService;
use yii\web\Request;
use yii\web\Response;

/**
 * Class GalleryController
 *
 * @package SK\GalleryModule\Api\Controller
 */
class GalleryController extends Controller
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
                    'index' => ['get', 'head'],
                    'view' => ['get', 'head'],
                    'create' => ['post'],
                    'update' => ['put', 'patch'],
                    'delete' => ['delete'],
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
     * Gets galleries
     *
     * @return mixed
     */
    public function actionIndex()
    {
        return Gallery::find()->limit(5)->all();
    }

    /**
     * Gets one gallery by id
     *
     * @param int $id
     * @return Gallery
     * @throws NotFoundHttpException
     */
    public function actionView(int $id): Gallery
    {
        return $this->findById($id);
    }

    /**
     * Create a new gallery
     *
     * @param Request $request
     * @return array[]|string[]
     */
    public function actionCreate(Request $request)
    {
        $form = new GalleryForm();

        if ($form->load($request->post()) && $form->isValid()) {
            $db = Yii::$app->db;
            $transaction = $db->beginTransaction();

            try {
                $data = [
                    'gallery_id' => $form->gallery_id,
                    'title' => $form->title,
                    'slug' => $form->slug,
                    'description' => $form->description,
                    'gallery_url' => $form->gallery_url,
                    'source_url' => $form->source_url,
                    'template' => $form->template,
                    'orientation' => $form->orientation,
                    'status' => $form->status,
                    'on_index' => $form->on_index,
                ];

                $images = [];
                if (!empty($form->image_urls)) {
                    $download = (bool) ($form->options['downloadImages'] ?? false);

                    $imagesCreator = new ImageCreator([
                        'download' => $download,
                    ]);

                    foreach ($form->image_urls as $imageUrl) {
                        $images[] = $imagesCreator->createFromArray(['source_url' => $imageUrl]);
                    }
                }

                $categories = [];
                if (!empty($form->category_ids)) {
                    $categories = Category::find()
                        ->where(['category_id' => $form->category_ids])
                        ->all();
                } elseif (!empty($form->category_titles)) {
                    $categories = Category::find()
                        ->where(['title' => $form->category_titles])
                        ->all();
                }


                $galleryCreator = new GalleryCreator;
                $gallery = $galleryCreator->createFromArray($data, [
                    'images' => $images,
                    'categories' => $categories,
                ]);

                $transaction->commit();

                return [
                    'message' => $gallery !== null ? "Gallery \"{$data['title']}\" created" : "Gallery \"{$data['title']}\" exists",
                ];
            } catch (\Exception $e) {
                $transaction->rollBack();

                return [
                    'error' => [
                        'code' => 422,
                        'message' => $e->getMessage(),
                    ],
                ];
            }
        } else {
            $errors = [];
            foreach($form->getErrorSummary(true) as $message) {
                $errors[] = $message;
            }

            return [
                'error' => [
                    'code' => 422,
                    'message' => "Cannot add gallery \"{$form->title}\"",
                    'errors' => $errors,
                ],
            ];
        }
    }

    /**
     * Update the category by id
     *
     * @param Request $request
     * @param int $id
     * @return array|array[]
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     */
    public function actionUpdate(Request $request, int $id): array
    {
        $gallery = $this->findById($id);

        $gallery->load(['Gallery' => $request->getBodyParams()]);

        if ($gallery->save()) {
            return [
                'message' => Yii::t('galleries', 'Gallery "{title}" has been updated', ['title' => $gallery->title]),
            ];

        } else {
            $errors = [];
            foreach($gallery->getErrorSummary(true) as $message) {
                $errors[] = $message;
            }

            return [
                'error' => [
                    'errors' => $errors,
                    'code' => 422,
                    'message' => Yii::t('galleries', 'Gallery "{title}" update fail', ['title' => $gallery->title]),
                ],
            ];
        }
    }

    /**
     * Delete the gallery by id
     *
     * @param Response $response
     * @param int $id
     * @return array[]|string
     * @throws NotFoundHttpException
     */
    public function actionDelete(Response $response, int $id)
    {
        $gallery = $this->findById($id);
        $galleryService = new GalleryService();

        if ($galleryService->delete($gallery)) {
            return '';
        }

        $response->setStatusCode(422);

        $errors = [];
        foreach($gallery->getErrorSummary(true) as $message) {
            $errors[] = $message;
        }

        return [
            'error' => [
                'code' => 422,
                'message' => 'Can\'t delete gallery',
                'errors' => $errors,
            ],
        ];
    }

    /**
     * Finds the Gallery model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     * @return Gallery the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findById(int $id): Gallery
    {
        $gallery = GalleryService::findById($id);

        if (null === $gallery) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $gallery;
    }
}
