<?php
namespace SK\GalleryModule\Widget;

use Yii;
use yii\base\Widget;

use SK\GalleryModule\Model\Category;

class Categories extends Widget
{
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
     * Лимит вывода категорий.
     *
     * @var integer
     */
    public $limit;

    /**
     * Группирует категории по первой букве
     *
     * @var boolean
     */
    public $groupByFirstLetter = false;

    /**
     * Включает кеш виджета.
     *
     * @var boolean
     */
    public $enableCache = true;

    /**
     * Время жизни кеша темплейта (html)
     *
     * @var integer
     */
    public $cacheDuration = 300;

    /**
     * @var array Коллекция массивов категорий.
     */
    public $items = [];

    private $cache;

    private $defaultCacheKey = 'gallery:widget:categories:';

    /**
     * Initializes the widget
     */
    public function init() {
        parent::init();

        $this->cache = Yii::$app->cache;

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
    public function run()
    {
        $cacheKey = $this->buildCacheKey();

        $html = $this->isCacheEnabled() ?  $this->cache->get($cacheKey) : false;

        if (false === $html) {
            $categories = $this->getItems();

            if (empty($categories)) {
                return;
            }

            $html = $this->render($this->template, [
                'categories' => $categories,
                'active_id' => $this->active_id,
            ]);

            if ($this->isCacheEnabled()) {
                $this->cache->set($cacheKey, $html, $this->cacheDuration);
            }
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

        $query = Category::find()
            ->select(['category_id', 'slug', 'image_id', 'title', 'description', 'param1', 'param2', 'param3', 'on_index', 'galleries_num'])
            ->with(['coverImage' => function ($query) {
                $query->select(['image_id', 'gallery_id', 'path', 'source_url'])
                    ->where(['enabled' => 1]);
            }])
            ->where(['enabled' => 1])
            ->orderBy($order);

        if (null !== $this->limit) {
            $query->limit((int) $this->limit);
        }

        if ($this->isGroupByFirstLetter()) {
            $lastLetter = '';
            $categories = [];

            foreach ($query->all() as $category) {
                $currentLetter = \mb_strtolower(\mb_substr($category->title, 0, 1));

                if (\is_numeric($currentLetter)) {
                    $currentLetter = '#';
                }

                $categories[$currentLetter][] = $category;
                $lastLetter = $currentLetter;
            }

            return $categories;
        }

        return $query->all();
    }

    /**
     * Включен\выключен кеш виджета.
     *
     * @return boolean
     */
    private function isCacheEnabled()
    {
        return (bool) $this->enableCache;
    }

    /**
     * Группировать или нет категории по первой букве.
     *
     * @return boolean
     */
    private function isGroupByFirstLetter()
    {
        return (bool) $this->groupByFirstLetter;
    }

    /**
     * Создает ключ для кеша.
     *
     * @return string
     */
    private function buildCacheKey()
    {
        return "{$this->defaultCacheKey}:{$this->order}:{$this->template}:{$this->active_id}";
    }
}
