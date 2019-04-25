<?php
namespace SK\GalleryModule\Admin;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use RS\Component\User\Model\User;
use yii\web\NotFoundHttpException;
use SK\GalleryModule\Model\Gallery;
use SK\GalleryModule\Model\ImportFeed;
use SK\GalleryModule\Import\CsvImporter;
use SK\GalleryModule\Import\ImageCreator;
use SK\GalleryModule\Import\GalleryCreator;
use SK\GalleryModule\Form\Admin\GalleriesImportForm;
use SK\GalleryModule\Form\Admin\CategoriesImportForm;

/**
 * ImportController
 */
class ImportController extends Controller
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
                    'delete-feed' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Импорт роликов через файл или текстовую форму
     *
     * @return mixed
     */
    public function actionGalleries(int $preset = 0)
    {
        $importFeed = ImportFeed::findOne($preset);

        if (null === $importFeed) {
            $importFeed = new ImportFeed();
        }

        $form = new GalleriesImportForm($importFeed);

        if ($form->load(Yii::$app->request->post()) && $form->isValid()) {
            $file = new \SplFileObject($form->csv_file->tempName);
            $file->setCsvControl($form->delimiter, $form->enclosure);

            $galleryCreator = new GalleryCreator([
                'skip_new_categories' => $form->skip_new_categories,
            ]);
            $imageCreator = new ImageCreator([
                'download' => !(bool) $form->external_images,
            ]);

            $csvImporter = new CsvImporter(
                $galleryCreator,
                $imageCreator,
                $form->fields,
                [
                    'skipFirstLine' => $form->skip_first_line,
                ]
            );
            $csvImporter->loadFile($file);
            $csvImporter->import([
                'template' => $form->template,
                'user_id' => $form->user_id,
                'status' => $form->status,
            ]);

            if (0 < $csvImporter->getInsertedRowsNum()) {
                Yii::$app->session->setFlash('success', Yii::t('gallery', '<b>{num}</b> galleries added', ['num' => $csvImporter->getInsertedRowsNum()]));
            }
        }

        $userNames = User::find()
            ->select('username')
            ->indexBy('user_id')
            ->column();

        $statusNames = Gallery::getStatusNames();

        $presetNames = ImportFeed::find()
            ->select(['name'])
            ->indexBy('feed_id')
            ->column();

        return $this->render('galleries', [
            'preset' => $preset,
            'form' => $form,
            'userNames' => $userNames,
            'statusNames' => $statusNames,
            'presetNames' => $presetNames,
        ]);
    }

    /**
     * Импорт категорий через файл или текстовую форму.
     *
     * @return mixed
     */
    public function actionCategories()
    {
        $form = new CategoriesImportForm;

        $form->csv_file = UploadedFile::getInstance($form, 'csv_file');

        if ($form->load(Yii::$app->request->post()) && $form->isValid()) {
            $form->save();

            if (0 < $form->getImportedRowsNum()) {
                Yii::$app->session->setFlash('success', Yii::t('videos', '<b>{num}</b> categories added or updated', ['num' => $form->getImportedRowsNum()]));
            }
        }

        return $this->render('categories', [
            'form' => $form,
        ]);
    }

    /**
     * Lists all ImportFeed models.
     *
     * @return mixed
     */
    public function actionListFeeds()
    {
        $query = ImportFeed::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'defaultPageSize' => 500,
                'pageSize' => 500,
            ],
        ]);

        return $this->render('list_feeds', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new ImportFeed model.
     * If creation is successful, the browser will be redirected to the 'videos' page.
     *
     * @return mixed
     */
    public function actionAddFeed()
    {
        $feed = new ImportFeed();

        if ($feed->load(Yii::$app->request->post()) && $feed->validate()) {
            $feed->save();

            return $this->redirect(['list-feeds']);
        }

        return $this->render('add_feed', [
            'feed' => $feed,
        ]);
    }

    /**
     * Редактирование существующего фида импорта
     *
     * @return mixed
     */
    public function actionUpdateFeed($id)
    {
        $feed = $this->findById($id);

        if ($feed->load(Yii::$app->request->post()) && $feed->validate()) {
            $feed->save();

            return $this->redirect(['list-feeds']);
        }

        return $this->render('update_feed', [
            'feed' => $feed,
        ]);
    }

    /**
     * Удаление фида импорта
     *
     * @return mixed
     */
    public function actionDeleteFeed($id)
    {
        $feed = $this->findById($id);

        if ($feed->delete()) {
            Yii::$app->session->setFlash('success', Yii::t('gallery', 'Feed "<b>{name}</b>" deleted', ['name' => $feed->getName()]));
        }

        return $this->redirect(['list-feeds']);
    }
    /**
     * Удаление фида импорта
     *
     * @return mixed
     */
    public function findById(int $id)
    {
        $feed = ImportFeed::findOne($id);

        if (null === $feed) {
            throw new NotFoundHttpException('The requested feed does not exist.');
        }

        return $feed;
    }
}
