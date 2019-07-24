<?php
namespace SK\GalleryModule\Admin\Form;

use yii\base\Model;
use yii\helpers\StringHelper;
use yii\data\ActiveDataProvider;
use RS\Component\User\Model\User;
use SK\GalleryModule\Model\Gallery;
use SK\GalleryModule\Model\GalleriesCategoriesMap;
/**
 * GalleryFilterForm represents the model behind the search form about ` SK\GalleryModule\Model\Gallery`.
 */
class GalleryFilterForm extends Model
{
    public $per_page = 50;
    public $galleries_ids = '';
    public $category_id;
    public $user_id;
    public $status;
    public $title;

    public $show_thumb = false;

    public $bulk_edit = false;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'status', 'per_page', 'category_id'], 'integer'],
            [['show_thumb', 'bulk_edit'], 'boolean'],

            ['galleries_ids', 'filter', 'skipOnEmpty' => true, 'filter' => function ($value) {
                return StringHelper::explode($value, $delimiter = ',', true, true);
            }],
            ['galleries_ids', 'each', 'rule' => ['integer'], 'skipOnEmpty' => true],
            ['galleries_ids', 'filter', 'filter' => 'array_filter', 'skipOnEmpty' => true],

            //['category_id', 'each', 'rule' => ['integer'], 'skipOnEmpty' => true ],
            //['categories_ids', 'filter', 'filter' => 'array_filter', 'skipOnEmpty' => true],

            [['title'], 'string'],
            ['title', 'filter', 'filter' => 'trim', 'skipOnEmpty' => true],
        ];
    }
	public function formName()
	{
		return '';
	}
    /**
     * Получает ролики постранично в разделе "все", отсортированные по дате.
     */
    public function search($params)
    {
        $query = Gallery::find()
        	->alias('g')
            ->with('coverImage');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'defaultPageSize' => $this->per_page,
                'pageSize' => $this->per_page,
            ],
            'sort'=> [
                'defaultOrder' => [
                    'gallery_id' => SORT_DESC,
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            $query->where('1=0');

            return $dataProvider;
        }

        $dataProvider->pagination->defaultPageSize = $this->per_page;
        $dataProvider->pagination->pageSize = $this->per_page;

        if ($this->title) {
            $query
                ->select(['g.*', 'MATCH (`g`.`title`, `g`.`description`) AGAINST (:query) AS `relevance`'])
                ->where('MATCH (`g`.`title`, `g`.`description`) AGAINST (:query IN BOOLEAN MODE)', [
                    ':query'=> $this->title,
                ])
                ->orderBy(['relevance' => SORT_DESC]);
        }

		if (!empty($this->category_id)) {
			$query->leftJoin(['gcm' => GalleriesCategoriesMap::tableName()], '`g`.`gallery_id` = `gcm`.`gallery_id`');
		}

        $query->andFilterWhere([
            'g.gallery_id' => $this->galleries_ids,
            'g.user_id' => $this->user_id,
            'g.status' => $this->status,
            'gcm.category_id' => $this->category_id,
        ]);

        return $dataProvider;
    }
}
