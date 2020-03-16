<?php
namespace SK\GalleryModule\CronJob;

use SK\GalleryModule\Service\Category;
use SK\CronModule\Handler\HandlerInterface;

class AssignCoverImages implements HandlerInterface
{
    public function run(): void
    {
        $rotator = new Category();
        $rotator->assignCoverImages();
    }
}
