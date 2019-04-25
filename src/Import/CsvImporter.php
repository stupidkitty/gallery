<?php
namespace SK\GalleryModule\Import;

use SK\GalleryModule\Model\Category;
//use SK\GalleryModule\Import\CsvParser;

class CsvImporter
{
    private $galleryCreator;
    private $imageCreator;

    private $csvFile;
    private $options;
    private $fields = [];

    protected $insertedRowsNum = 0;

    public function __construct(GalleryCreator $galleryCreator, ImageCreator $imageCreator, array $fields = [], array $options = [])
    {
        $this->galleryCreator = $galleryCreator;
        $this->imageCreator = $imageCreator;
        $this->fields = $fields;
        $this->options = $options;
    }

    /**
     * Импорт галерей из загруженного файла.
     *
     * @param array $extra Измененные вручную поля для каждой строчки.
     */
    public function import(array $extra = [])
    {
        $csvParser = new CsvParser($this->csvFile, $this->fields, [
            'skipFirstLine' => isset($this->options['skipFirstLine']) ? (bool) $this->options['skipFirstLine'] : true,
        ]);

        $csvParser->each(function ($line) use ($extra) {
            $line = array_merge($line, $extra);

            $categories = [];
            if (!empty($line['categories_ids'])) {
                $categories = Category::find()
                    ->select(['category_id', 'title'])
                    ->where(['category_id' => explode(',', $line['categories_ids'])])
                    ->all();
                unset($line['categories_ids']);

                // Или категории по названиям.
            } elseif (!empty($line['categories'])) {
                $categories = Category::find()
                    ->select(['category_id', 'title'])
                    ->where(['title' => explode(',', $line['categories'])])
                    ->all();
                unset($line['categories']);
            }

            // Если у галеры есть изображения, вынесем их в отдельный массив.
            $images = [];
            if (!empty($line['images'])) {
                $imagesUrls = explode(',', $line['images']);
                foreach ($imagesUrls as $i => $imageUrl) {
                    $image = $this->imageCreator->createFromArray([
                        'path' => $imageUrl,
                        'source_url' => $imageUrl,
                        'ordering' => $i,
                    ]);

                    if (false !== $image) {
                        $images[] = $image;
                    }
                }
                unset($line['images']);
            }

            $gallery = $this->galleryCreator->createFromArray($line, [
                'categories' => $categories,
                'images' => $images,
            ]);

            if (false !== $gallery) {
                $this->insertedRowsNum ++;
            }
        });
    }

    /**
     * Загрузка цсв файла для обработки
     *
     * @param SplFileObject $file Цсв файл.
     */
    public function loadFile(\SplFileObject $file): void
    {
        $this->csvFile = $file;
    }

    /**
     * Установка полей цсв файла.
     *
     * @param array $fields Поля цсв файла.
     */
    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    /**
     * Установка опций для обработки.
     *
     * @param array $options Опции для обработки файла или вставки объектов.
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * Количество успешно вставленных строк
     *
     * @return integer
     */
    public function getInsertedRowsNum(): int
    {
        return $this->insertedRowsNum;
    }
}
