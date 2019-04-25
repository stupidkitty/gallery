<?php
namespace SK\GalleryModule;

use Yii;
use yii\base\Module as BaseModule;
use yii\i18n\PhpMessageSource;
use yii\console\Application as ConsoleApplication;

/**
 * This is the main module class of the gallery extension.
 */
class Module extends BaseModule
{
    /** @var string $controllerNamespace Дефолтный неймспейс для контроллеров */
    public $controllerNamespace = 'SK\GalleryModule\Controller';

    /** @var string $defaultRoute Дефолтный роут для модуля. */
    public $defaultRoute = 'main/index';

    /** @var string $layoutPath Директория темплейтов */
    public $layoutPath = '';

    /** @var string $contentDirectory Директория для фоток в хранилище. */
    public $contentDirectory;
    
    /** @var string $contentBaseUrl Базовый урл для фотографий. */
    public $contentBaseUrl;

     /**
      * @inheritdoc
      */
     public function __construct($id, $parent = null, $config = [])
     {
         // дефолтный путь до папки темплейтов.
         $this->setViewPath(__DIR__ . '/Resources/views');

         parent::__construct ($id, $parent, $config);
     }

    public function init()
    {
        parent::init();

        // контреллеры для консольных команд
        if (Yii::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'SK\GalleryModule\Command';
        }

        $this->configureContainer();

        // перевод
        if (Yii::$app->has('i18n') && empty(Yii::$app->get('i18n')->translations['gallery'])) {
            Yii::$app->get('i18n')->translations['gallery'] = [
                'class' => PhpMessageSource::class,
                'basePath' => __DIR__ . '/Resources/i18n',
                'sourceLanguage' => 'en-US',
            ];
        }
    }

    /**
     * Конфигурирует DI контейнер.
     */
    protected function configureContainer()
    {
        $di = Yii::$container;

        try {
            $di->set(Service\Image::class);
            $di->set(Service\Gallery::class);
        } catch (Exception $e) {
            die($e);
        }
    }
}
