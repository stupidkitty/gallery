<?php
namespace SK\GalleryModule;

use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\i18n\PhpMessageSource;
use yii\console\Application as ConsoleApplication;
use yii\web\Application as WebApplication;

//use RS\Module\UserModule\Event\VisitEvent;

class Bootstrap implements BootstrapInterface
{
    /**
     * {@inheritdoc}
     */
    public function bootstrap($app)
    {
        //if ($app instanceof WebApplication) {
            //$this->registerEvents($app);
        //}
        $this->configureContainer();
    }
    /**
     * @inheritdoc
     */
    /*protected function registerEvents($app)
    {

    }*/
    /**
     * @inheritdoc
     */
    protected function configureContainer()
    {
    }
}
