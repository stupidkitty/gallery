<?php
namespace SK\GalleryModule\Form\Api;

use yii\base\Model;
use SK\GalleryModule\Model\Gallery;

/**
 * GalleryForm Форма редактирования видео ролика (поста)
 */
class GalleryForm extends Model
{
    public $gallery_id;
    public $title;
    public $slug;
    public $description;
    public $gallery_url;
    public $source_url;
    public $template;
    public $orientation;
    public $status;
    public $on_index;
    public $published_at;
    /** @var array $category_ids Список айди категорий видео ролика. */
    public $category_ids = [];
    public $category_titles = [];
    /** @var array $images Список урлов скриншотов для видео ролика. */
    public $image_urls = [];
    public $options = [];

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['slug', 'title', 'gallery_url', 'source_url', 'template'], 'string', 'max' => 255],
            [['description'], 'string', 'max' => 3000],
            [['gallery_id', 'orientation', 'status'], 'integer'],
            ['gallery_id', 'unique', 'targetClass' => Gallery::class, 'when' => function () {
                return !empty($this->gallery_id);
            }],
            [['on_index'], 'boolean'],
            [['published_at'], 'safe'],

            ['category_ids', 'each', 'rule' => ['integer']],
            ['category_ids', 'filter', 'filter' => 'array_filter', 'skipOnEmpty' => true],
            ['category_ids', 'default', 'value' => []],

            ['category_titles', 'each', 'rule' => ['string']],
            ['category_titles', 'filter', 'filter' => 'array_filter', 'skipOnEmpty' => true],
            ['category_titles', 'default', 'value' => []],

            ['image_urls', 'each', 'rule' => ['string']],
            ['image_urls', 'filter', 'filter' => 'array_filter', 'skipOnEmpty' => true],
            ['image_urls', 'default', 'value' => []],

            ['options', 'each', 'rule' => ['string']],
            ['options', 'filter', 'filter' => 'array_filter', 'skipOnEmpty' => true],
            ['options', 'default', 'value' => []],

            [['title', 'description'], 'filter', 'filter' => function ($value) {
                $value = preg_replace('/\s+/', ' ', $value);
                return trim($value);
            }],

            [['slug', 'gallery_url', 'source_url', 'template'], 'trim'],
            ['slug', 'default', 'value' => ''],
            ['status', 'default', 'value' => 0],
            ['orientation', 'default', 'value' => 0],
            ['on_index', 'default', 'value' => 1],
            ['published_at', 'default', 'value' => null],
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
        return $this->validate();
    }
}
