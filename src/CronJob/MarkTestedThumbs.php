<?php
namespace SK\GalleryModule\CronJob;

use SK\GalleryModule\Service\Rotator;
use SK\CronModule\Handler\HandlerInterface;

class MarkTestedThumbs implements HandlerInterface
{
    public function run()
    {
        $rotator = new Rotator();
        $rotator->markAsTestedRows();
    }
}
