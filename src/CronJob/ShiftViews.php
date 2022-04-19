<?php
namespace SK\GalleryModule\CronJob;

use SK\GalleryModule\Service\Rotator;
use App\Infrastructure\Cron\HandlerInterface;

class ShiftViews implements HandlerInterface
{
    public function run(): void
    {
        $rotator = new Rotator();
        $rotator->shiftHistoryCheckpoint();
    }
}
