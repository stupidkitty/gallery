<?php
namespace SK\GalleryModule\CronJob;

use SK\VideoModule\Service\Category;
use SK\CronModule\Handler\HandlerInterface;

class ClearOldStats implements HandlerInterface
{
    public function run(): void
    {
        $rotator = new Category();
        $rotator->clearOldStats();
    }
}
