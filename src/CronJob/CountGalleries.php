<?php
namespace SK\GalleryModule\CronJob;

use SK\GalleryModule\Service\Category;
use App\Infrastructure\Cron\HandlerInterface;

class CountGalleries implements HandlerInterface
{
    public function run(): void
    {
        $rotator = new Category();
        $rotator->countGalleries();
    }
}
