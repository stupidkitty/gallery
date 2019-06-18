<?php
namespace SK\GalleryModule\Widget;

use Yii;
use yii\base\Widget;

use SK\GalleryModule\Model\Category;

class Categories extends Widget
{
    private $cacheKey = 'gallery:widget:categories:';
    /**
     * @var int Идентификатор текущей активной категории;
     */
    public $active_id = null;
    /**
     * @var string path to template
     */
    public $template;
    /**
     * @var array|string сортировка элементов
     * Можно использовать следующие параметры:
     * - id: integer, идентификатор категории
     * - title: string, название
     * - position: integer, порядковый номер при ручной сортировке
     * - clicks: integer, клики по категориям тумб.
     */
    public $order = 'title';
    /**
     * @var int Время жизни кеша темплейта (html)
     */
    public $cacheDuration = 300;
    /**
     * @var array Коллекция массивов категорий.
     */
    public $items = [];

    /**
     * Initializes the widget
     */
    public function init() {
        parent::init();

        if (!in_array($this->order, ['id', 'title', 'position', 'clicks'])) {
            $this->order = 'title';
        }
    }

    public function getViewPath()
    {
        return Yii::getAlias('@root/views/gallery');
    }

    /**
     * Runs the widget
     *
     * @return string|void
     */
    public function run() {
        $cacheKey = $this->buildCacheKey();

        $html = Yii::$app->cache->get($cacheKey);

        if (false === $html) {
            $categories = $this->getItems();

            if (empty($categories)) {
                return;
            }

            $html = $this->render($this->template, [
                'categories' => $categories,
                'active_id' => $this->active_id,
            ]);

            Yii::$app->cache->set($cacheKey, $html, $this->cacheDuration);
        }

        return $html;
    }

    private function getItems()
    {
        if ('title' === $this->order) {
            $order = ['title' => SORT_ASC];
        } elseif ('position' === $this->order) {
            $order = ['position' => SORT_ASC];
        } elseif ('id' === $this->order) {
            $order = ['category_id' => SORT_ASC];
        } elseif ('clicks' === $this->order) {
            $order = ['popularity' => SORT_DESC];
        }

        $items = Category::find()
            ->select(['category_id', 'slug', 'image_id', 'title', 'description', 'param1', 'param2', 'param3', 'on_index', 'galleries_num'])
            ->with(['coverImage' => function ($query) {
                $query->select(['image_id', 'gallery_id', 'path', 'source_url'])
                    ->where(['enabled' => 1]);
            }])
            ->where(['enabled' => 1])
            ->orderBy($order)
            ->all();

        return $items;
    }

    private function buildCacheKey()
    {
        return "{$this->cacheKey}:{$this->order}:{$this->template}:{$this->active_id}";
    }
}