<?php
namespace SK\GalleryModule\CronJob;

use SK\GalleryModule\Service\Gallery;
use SK\CronModule\Handler\HandlerInterface;

class UpdateMaxCtr implements HandlerInterface
{
    public function run()
    {
        $rotator = new Gallery();
        $rotator->updateMaxCtr();
    }
}
