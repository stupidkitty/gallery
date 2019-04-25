<?php
namespace SK\GalleryModule\Widget;

use Yii;
use yii\base\Widget;
use RS\Component\Core\Settings\SettingsInterface;
use SK\GalleryModule\Provider\RelatedProvider;

class RelatedGalleries extends Widget
{
    private $cacheKey = 'gallery:widget:related_galleries:';
    /**
     * @var string путь к темплейту виджета
     */
    public $template;
    /**
     * @var integer $gallery_id
     */
    public $gallery_id = 0;
    /**
     * @var array Коллекция фотосетов.
     */
    public $galleries;
    /**
     * @var array диапазон показа релейтедов
     * Пример: 'range' => [1, 5],
     */
    public $range;

    public $enabled;

    public function getViewPath()
    {
        return Yii::getAlias('@root/views/gallery');
    }

    /**
     * Initializes the widget
     */
    public function init() {
        parent::init();

        if (empty($this->gallery_id)) {
            return;
        }

        if (null === $this->enabled) {
            $this->enabled = Yii::$container->get(SettingsInterface::class)->get('related_enable', false, 'gallery');
        }
    }

    /**
     * Runs the widget
     *
     * @return string|void
     */
    public function run()
    {
        if (!$this->enabled) {
            return;
        }

        $cacheKey = $this->buildCacheKey();

        $html = Yii::$app->cache->get($cacheKey);

        if (false === $html) {
            $this->buildRelated();

            if (empty($this->galleries)) {
                return;
            }

            if (is_array($this->range)) {
                $rangeStart = ($this->range[0] > 0) ? $this->range[0] - 1 : 0 ;
                $rangeEnd = (!isset($this->range[1])) ? 1 : $this->range[1] ;

                $html = $this->render($this->template, [
                    'galleries' =>  array_slice($this->galleries, $rangeStart, $rangeEnd),
                ]);
            } else {
                $html = $this->render($this->template, [
                    'galleries' => $this->galleries,
                ]);
            }

            Yii::$app->cache->set($cacheKey, $html, 3600);
        }

        return $html;
    }

    /**
     * Получает "похожие" видео.
     *
     * @return array
     */
    private function buildRelated()
    {
        if (null !== $this->galleries) {
            return $this->galleries;
        }

        $relatedProvider = new RelatedProvider;
        $this->galleries = $relatedProvider->getModels($this->gallery_id);

        return $this->galleries;
    }

    private function buildCacheKey()
    {
        $key = $this->cacheKey;

        $key .= $this->gallery_id;
        $key .= isset($this->range[0]) ? ":range:{$this->range[0]}" : '';
        $key .= isset($this->range[1]) ? ":{$this->range[1]}" : '';

        return $key;
    }
}
