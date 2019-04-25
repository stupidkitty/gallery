<?php
namespace SK\GalleryModule\Service;

use Yii;
use SK\GalleryModule\Model\ImageInterface;
use SK\GalleryModule\Model\RotationStats;
use SK\GalleryModule\Model\CropProfile;
use League\Flysystem\FilesystemInterface;

class Image
{
    protected $filesystem;
    protected $imageFormatNames;

    public function __construct(FilesystemInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Удаляет фотографии окончательно, вместе с файлом.
     */
    public function delete(ImageInterface $image)
    {
        if ('' !== $image->getPath()) {
            foreach ($this->getImageFormatNames() as $imageFormatName) {
                $path = "photos/{$imageFormatName}" . $image->getPath();

                if ($this->storage->has($path)) {
                    $this->storage->delete($path);
                }
            }
        }

        if ($image->delete()) {
            RotationStats::deleteAll(['image_id' => $image->getId()]);
        }
    }

    /**
     * @return array Список форматов изображений (нарезанных)
     */
    public function getImageFormatNames(): array
    {
        if (null === $this->imageFormatNames) {
            $crops = CropProfile::find()
                ->select(['name'])
                ->column();

            $crops[] = 'src';

            $this->imageFormatNames = $crops;
        }

        return $this->imageFormatNames;
    }
}
