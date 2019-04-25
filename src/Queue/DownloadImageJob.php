<?php
namespace SK\GalleryModule\Queue;

use Throwable;
use Exception;
use SplFileInfo;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\helpers\FileHelper;

use League\Flysystem\FilesystemInterface;
use League\Flysystem\FileExistsException;

use SK\GalleryModule\Model\Image;
use RS\Component\Core\Generator\FilepathGenerator;
use SK\GalleryModule\Service\ThumbsGenerator;

class DownloadImageJob extends BaseObject implements JobInterface
{
    public $image_id;

    public function execute($queue)
    {
        $filesystem = Yii::$container->get(FilesystemInterface::class);
        $pathGenerator = new FilepathGenerator;

        $image = Image::findOne($this->image_id);

        if (null === $image) {
            throw new Exception('Image not found. Id: ' . $this->image_id);
        }

        $tmpDirectory =  Yii::getAlias('@runtime/tmp');
        $baseDirectory = Yii::getAlias('@root/storage/photos');

        if (!\is_dir($tmpDirectory)) {
            FileHelper::createDirectory($tmpDirectory, 0755);
        }

        try {
            $sourceFilename = \strtolower(\pathinfo($image->getSourceUrl(), PATHINFO_BASENAME));
            $imageBlob = \file_get_contents($image->getSourceUrl());

            if (false === \file_put_contents("$tmpDirectory/$sourceFilename", $imageBlob)) {
                throw new Exception('Cannot write file');
            }

            $file = new SplFileInfo("$tmpDirectory/$sourceFilename");
            $fileHash = \md5_file($file->getPathname());
            $path = $pathGenerator->generateByHash($file);

            try {
                $filesystem->write("photos/src{$path}", \file_get_contents($file->getPathname()));

                $image->setPath($path);
                $image->setHash($fileHash);
                $image->enable();
                $image->save();

                \unlink($file->getPathname());

                $thumbsGenerator = new ThumbsGenerator($filesystem);
                $thumbsGenerator->generate($image);
            } catch(FileExistsException $e) {
                $image->disable();
                $image->save();

                \unlink($file->getPathname());
            }
        } catch (Throwable $e) {
            echo $image->getSourceUrl() . ' not found' . PHP_EOL;
            $image->disable();
            $image->save();
        }
    }
}
