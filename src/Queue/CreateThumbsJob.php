<?php
namespace SK\GalleryModule\Queue;

use Exception;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use League\Flysystem\FilesystemInterface;
use SK\GalleryModule\Model\Image;
use SK\GalleryModule\Service\ThumbsGenerator;

class CreateThumbsJob extends BaseObject implements JobInterface
{
    public $image_id;

    public function execute($queue)
    {
        $filesystem = Yii::$container->get(FilesystemInterface::class);

        $image = Image::findOne($this->image_id);

        if (null === $image) {
            throw new Exception('Image not found. Id: ' . $this->image_id);
        }

        $thumbsGenerator = new ThumbsGenerator($filesystem);
        $thumbsGenerator->generate($image);
    }
}
