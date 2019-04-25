<?php
namespace SK\GalleryModule\Statistic\Report;

class GalleryStatisticReport
{
    private $totalGalleries = 0;
    private $disabledGalleries = 0;
    private $activeGalleries = 0;
    private $moderateGalleries = 0;
    private $deletedGalleries = 0;
    private $autopostingGalleries = 0;

    private $totalCategories = 0;
    private $enabledCategories = 0;
    private $disabledCategories = 0;

    private $totalImages = 0;

    public function getTotalGalleries()
    {
        return $this->totalGalleries;
    }

    public function setTotalGalleries($totalGalleries)
    {
        $this->totalGalleries = (int) $totalGalleries;
    }

    public function getDisabledGalleries()
    {
        return $this->disabledGalleries;
    }

    public function setDisabledGalleries($disabledGalleries)
    {
        $this->disabledGalleries = (int) $disabledGalleries;
    }

    public function getActiveGalleries()
    {
        return $this->activeGalleries;
    }

    public function setActiveGalleries($activeGalleries)
    {
        $this->activeGalleries = (int) $activeGalleries;
    }

    public function getModerateGalleries()
    {
        return $this->moderateGalleries;
    }

    public function setModerateGalleries($moderateGalleries)
    {
        $this->moderateGalleries = (int) $moderateGalleries;
    }

    public function getDeletedGalleries()
    {
        return $this->deletedGalleries;
    }

    public function setDeletedGalleries($deletedGalleries)
    {
        $this->deletedGalleries = (int) $deletedGalleries;
    }

    public function getAutopostingGalleries()
    {
        return $this->autopostingGalleries;
    }

    public function setAutopostingGalleries($autopostingGalleries)
    {
        $this->autopostingGalleries = (int) $autopostingGalleries;
    }

    public function getTotalCategories()
    {
        return $this->totalCategories;
    }

    public function setTotalCategories($totalCategories)
    {
        $this->totalCategories = (int) $totalCategories;
    }

    public function getEnabledCategories()
    {
        return $this->enabledCategories;
    }

    public function setEnabledCategories($enabledCategories)
    {
        $this->enabledCategories = (int) $enabledCategories;
    }

    public function getDisabledCategories()
    {
        return $this->disabledCategories;
    }

    public function setDisabledCategories($disabledCategories)
    {
        $this->disabledCategories = (int) $disabledCategories;
    }

    public function getTotalImages()
    {
        return $this->totalImages;
    }

    public function setTotalImages($totalImages)
    {
        $this->totalImages = (int) $totalImages;
    }
}
