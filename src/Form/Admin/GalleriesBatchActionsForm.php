<?php
namespace SK\GalleryModule\Form\Admin;

use yii\base\Model;
use RS\Component\User\Model\User;
use SK\GalleryModule\Model\Gallery;
use SK\GalleryModule\Model\Category;

class GalleriesBatchActionsForm extends Model
{
    /**
     * Checkboxes
     */
    public $isChangeUser;
    public $isChangeStatus;
    public $isAddCategories;
    public $isDeleteCategories;
    public $isChangeOrientation;

    public $galleries_ids;
    public $user_id;
    public $orientation;
    public $status;
    public $add_categories_ids;
    public $delete_categories_ids;

    public function __construct($config = [])
    {
        parent::__construct($config = []);
    }

    public function rules()
    {
        return [
            [
                [
                    'isChangeUser',
                    'isChangeStatus',
                    'isAddCategories',
                    'isDeleteCategories',
                    'isChangeOrientation',
                ], 'boolean'
            ],

            [['user_id', 'orientation', 'status'], 'integer'],
            ['user_id', 'exist', 'targetClass' => User::class, 'skipOnEmpty' => true],

            ['add_categories_ids', 'each', 'rule' => ['integer'], 'skipOnEmpty' => true ],
            ['add_categories_ids', 'filter', 'filter' => 'array_filter', 'skipOnEmpty' => true],
            ['add_categories_ids', 'default', 'value' => []],

            ['delete_categories_ids', 'each', 'rule' => ['integer'], 'skipOnEmpty' => true ],
            ['delete_categories_ids', 'filter', 'filter' => 'array_filter', 'skipOnEmpty' => true],
            ['delete_categories_ids', 'default', 'value' => []],

            ['galleries_ids', 'each', 'rule' => ['integer']],
            ['galleries_ids', 'filter', 'filter' => 'array_filter'],
            ['galleries_ids', 'required', 'message' => 'Galleries not selected'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function formName()
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $query = Gallery::find()
            ->select(['gallery_id', 'user_id', 'orientation', 'status'])
            ->with(['categories'])
            ->where(['gallery_id' => $this->galleries_ids]);

        foreach ($query->batch(50) as $galleries) {
            foreach ($galleries as $gallery) {
                // Изменение пользователя
                if ((bool) $this->isChangeUser) {
                    $gallery->user_id = $this->user_id;
                }

                // Изменение пользователя
                if ((bool) $this->isChangeOrientation) {
                    $gallery->orientation = $this->orientation;
                }

                // Изменение статуса
                if ((bool) $this->isChangeStatus && !empty($this->status)) {
                    $gallery->status = $this->status;
                }

                $gallery->save();

                // Добавление категории
                if ((bool) $this->isAddCategories) {
                    $categories = Category::find()
                        ->where(['category_id' => $this->add_categories_ids])
                        ->all();

                    foreach ($categories as $category) {
                        $gallery->addCategory($category);
                    }
                }
                // Удаление категории
                if ((bool) $this->isDeleteCategories) {
                    $categories = Category::find()
                        ->where(['category_id' => $this->delete_categories_ids])
                        ->all();

                    foreach ($categories as $category) {
                        $gallery->removeCategory($category);
                    }
                }
            }
        }
    }
}
