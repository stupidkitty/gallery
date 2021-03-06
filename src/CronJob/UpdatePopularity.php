<?php
namespace SK\GalleryModule\CronJob;

use SK\GalleryModule\Service\Category;
use SK\CronModule\Handler\HandlerInterface;

class UpdatePopularity implements HandlerInterface
{
    public function run(): void
    {
        $rotator = new Category();
        $rotator->updatePopularity();
    }
}
