<?php
namespace SK\GalleryModule\CronJob;

use SK\GalleryModule\Service\Category;
use SK\CronModule\Handler\HandlerInterface;

class CountGalleries implements HandlerInterface
{
    public function run(): void
    {
        $rotator = new Category();
        $rotator->countGalleries();
    }
}
