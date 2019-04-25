<?php
namespace SK\GalleryModule\Form\Admin;

use yii\base\Model;
use yii\helpers\StringHelper;

/**
 * Модель для обработки формы импорта категорий через цсв файлы или просто текст.
 */
class CategoryForm extends Model
{
	public $title;
	public $slug;
	public $meta_title;
	public $meta_description;
	public $h1;
	public $description;
	public $seotext;
	public $param1;
	public $param2;
	public $param3;
	public $on_index;
	public $enabled;

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
    public function rules()
    {
        return [
            [['title', 'slug', 'meta_title', 'meta_description', 'h1', 'description', 'seotext', 'param1', 'param2', 'param3'], 'string'],
            [['on_index', 'enabled'], 'boolean'],

            [['title', 'slug', 'meta_title', 'meta_description', 'h1'] , 'filter', 'filter' => function ($attribute) {
                return StringHelper::truncate($attribute, 255, false);
            }],
            [['title', 'slug', 'meta_title', 'meta_description', 'h1', 'description', 'seotext', 'param1', 'param2', 'param3'], 'trim'],
            [['title'], 'required'],

            ['slug', 'default', 'value' => ''],
        ];
    }

    /**
     * @inheritdoc
     */
    public function isValid()
    {
        return $this->validate();
    }
}
