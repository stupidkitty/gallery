<?php
namespace SK\GalleryModule\Service;

use Yii;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

use League\Flysystem\FilesystemInterface;
use League\Flysystem\FileExistsException;

use SK\GalleryModule\Model\ImageInterface;
use SK\GalleryModule\Model\CropProfile;

class ThumbsGenerator
{
    private $cropProfiles = [];
    private $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;

        $this->cropProfiles = CropProfile::find()
            ->all();
    }

    public function generate(ImageInterface $image)
    {
        if (empty($this->cropProfiles)) {
            return;
        }

        $baseDirectory = Yii::getAlias('@root/storage/photos');

        foreach ($this->cropProfiles as $profile) {
            $srcFile = $baseDirectory . '/src' . $image->getPath();
            $outFile = $baseDirectory . '/' . $profile->getName() . $image->getPath();
            $fsOutDirectory = \dirname('photos/' . $profile->getName() . $image->getPath());

            try {
                $result = $this->filesystem->createDir($fsOutDirectory);

                if (false === $result) {
                    throw new Exception('Cannot create directory: ' . \dirname($outFile));
                }

                $process = new Process("convert '{$srcFile}' {$profile->command} '{$outFile}'");
                $process->run();

                // executes after the command finishes
                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }
            } catch (ProcessFailedException | Throwable $pfe) {
                echo $pfe->getMessage();
                echo PHP_EOL;
            }
        }
    }
}
