<?php
namespace SK\GalleryModule\Import;

use Yii;
use SK\GalleryModule\Model\Image;
use SK\GalleryModule\Queue\DownloadImageJob;

class ImageCreator
{
    private $options;

    /**
     * Конструктор создателя картинок.
     *
     * @param array $options Опции для создания изображений.
     */
    public function __construct(array $options = []) {
        $this->options = $options;
    }

    public function createFromArray(array $data, array $extra = [])
    {
        $image = new Image;

        $image->setAttributes($data);
        $image->created_at = gmdate('Y-m-d H:i:s');

        if (!$image->save(true)) {
            return false;
        }

        if (isset($this->options['download']) && true === $this->options['download']) {
            Yii::$app->queue
                ->push(new DownloadImageJob([
                    'image_id' => $image->getId(),
                ]));
        }

        return $image;
    }
}
