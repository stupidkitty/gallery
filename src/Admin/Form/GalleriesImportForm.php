<?php
namespace SK\GalleryModule\Admin\Form;

use yii\base\Model;
use yii\web\UploadedFile;
use SK\GalleryModule\Model\ImportFeed;

/**
 * Пометка: Сделать проверку на соответствие полей. Если не соответствует - писать в лог.
 */

/**
 * Модель для обработки формы импорта категорий через цсв файлы или просто текст.
 */
class GalleriesImportForm extends Model
{
    public $delimiter;
    public $enclosure;
    public $fields;

    public $csv_file;

    /**
     * @var string $default_date Дефолтная дата поста (текущая)
     */
    public $default_date;
    /**
     * @var string $set_published_at_method Метод заполнения времени постинга.
     */
    public $set_published_at_method;
    /**
     * @var int $user_id Автор добавленных постов.
     */
    public $user_id;
    /**
     * @var int $status Статус новой записи.
     */
    public $status;
    /**
     * @var string $template Шаблон вывода вставленного видео.
     */
    public $template;
    /**
     * @var boolean $skip_new_categories Пропускать создание новых видео, если исходный урл уже есть.
     */
    public $skip_duplicate_urls;
    /**
     * @var boolean $skip_new_categories Пропускать создание новых видео, если emebd код такой уже есть.
     */
    //public $skip_duplicate_embeds;
    /**
     * @var boolean $skip_new_categories Не создавать новые категории.
     */
    public $skip_new_categories;
    /**
     * @var boolean $external_images Будут использоваться внешние тумбы или скачиваться и нарезаться на сервере.
     */
    public $external_images;
    /**
     * @var boolean Пропуск первой строчки в CSV.
     */
    public $skip_first_line;
    /**
     * @var int $imported_rows_num Количество вставленных записей.
     */
    public $imported_rows_num = 0;
    /**
     * @var array $categories Категории раздела видео.
     */
    protected $categories;
    /**
     * @var array $option Опции для тега select, отвечающего за набор полей csv.
     */
    protected $options = [];
    /**
     * @var array $not_inserted_rows Забракованные строчки из CSV.
     */
    //protected $not_inserted_rows = [];
    /**
     * @var array $not_inserted_ids Не вставленные иды видео, если такие были.
     */
    //protected $not_inserted_ids = [];
    /**
     * @var \DateTime $startDate
     */
    /*protected $startDate;

    protected $timeIntervalGenerator;*/

    public function __construct(ImportFeed $importFeed, $config = [])
    {
        parent::__construct($config);

        //set_time_limit(0);

        $this->setAttributes($importFeed->getAttributes());
        $this->setOptions($importFeed->getFieldsOptions());

        $this->default_date = (new \DateTime('now'))->format('Y-m-d H:i:s');
        $this->set_published_at_method = 'auto_add';
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
    public function rules()
    {
        return [
            [['delimiter', 'fields'], 'required'],
            ['fields', 'each', 'rule' => ['string'], 'skipOnEmpty' => false],
            [['delimiter', 'enclosure', 'template', 'default_date', 'set_published_at_method'], 'string'],
            [['delimiter', 'enclosure', 'template', 'default_date', 'set_published_at_method'], 'filter', 'filter' => 'trim'],
            [['skip_duplicate_urls', 'skip_new_categories', 'external_images', 'skip_first_line'], 'boolean'],
            [['status', 'user_id'], 'integer'],

            [['csv_file'], 'file', 'checkExtensionByMimeType' => false, 'skipOnEmpty' => true, 'extensions' => ['csv'], 'maxFiles' => 1, 'mimeTypes' => ['text/plain', 'text/csv']],
        ];
    }

    public function isValid()
    {
        $this->csv_file = UploadedFile::getInstance($this, 'csv_file');

        return $this->validate();
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return array
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }
}
