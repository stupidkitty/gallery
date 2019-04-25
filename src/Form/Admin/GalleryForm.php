<?php
namespace SK\GalleryModule\Form\Admin;

use yii\base\Model;
use yii\web\UploadedFile;
use yii\helpers\StringHelper;

/**
 * GalleryForm Форма редактирования галереи (поста)
 */
class GalleryForm extends Model
{
    public $title;
    public $slug;
    public $description;
    public $template;
    /** @var integer $image_id Идентификатор постера из числа скриншотов видео. */
    public $image_id;
    /** @var integer $user_id Идентификатор автора (владельца). */
    public $user_id;
    public $orientation;
    public $status;
    public $on_index;
    public $published_at;
    public $published_at_method;
    /** @var array $categories_ids Список айди категорий. */
    public $categories_ids = [];
    public $images;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->on_index = 1;
        $this->orientation = 1;
        $this->status = 0;
        $this->user_id = 0;
        $this->image_id = 0;
        $this->published_at = gmdate('Y-m-d H:i:s');
        $this->published_at_method = 'dont-set';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['slug', 'title', 'description', 'template', 'published_at', 'published_at_method'], 'string'],
            [['image_id', 'user_id', 'orientation', 'status'], 'integer'],
            [['on_index'], 'boolean'],

            ['categories_ids', 'each', 'rule' => ['integer']],
            ['categories_ids', 'filter', 'filter' => 'array_filter', 'skipOnEmpty' => true],
            ['categories_ids', 'default', 'value' => []],

            [['images'], 'image', 'skipOnEmpty' => true, 'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'maxFiles' => 300],
            ['images', 'default', 'value' => []],

            [['title', 'description'], 'filter', 'filter' => function ($value) {
                return preg_replace('/\s+/', ' ', $value);
            }],
            [['title', 'slug', 'template'] , 'filter', 'filter' => function ($attribute) {
                return StringHelper::truncate($attribute, 255, false);
            }],

            [['title'], 'required'],
            [['title', 'description', 'slug', 'template'], 'trim'],

            ['status', 'default', 'value' => 0],
            ['orientation', 'default', 'value' => 1],
            ['on_index', 'default', 'value' => 1],
            ['user_id', 'default', 'value' => 0],
            ['image_id', 'default', 'value' => 0],
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
     * Валидирует форму и возвращает результат валидации.
     * true если все правила успешно пройдены.
     *
     * @return boolean
     */
    public function isValid()
    {
        $this->images = UploadedFile::getInstances($this, 'images');

        return $this->validate();
    }
}
