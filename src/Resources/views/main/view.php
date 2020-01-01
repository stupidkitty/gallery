<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\DetailView;

$this->title = Yii::t('gallery', 'galleries');
$this->params['subtitle'] = Yii::t('gallery', 'info');

$this->params['breadcrumbs'][] = ['label' => Yii::t('gallery', 'galleries'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $gallery->title;

?>

<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title"><?= Html::encode($gallery->title) ?></h3>

        <div class="btn-group pull-right">
            <?= Html::a('<i class="fa fa-fw fa-plus text-green"></i>' . Yii::t('gallery', 'add'), ['create'], ['class' => 'btn btn-default btn-sm']) ?>
            <?= Html::a('<i class="fa fa-fw fa-edit text-blue"></i>' . Yii::t('gallery', 'edit'), ['update', 'id' => $gallery->getId()], ['class' => 'btn btn-default btn-sm']) ?>
            <?= Html::a('<i class="fa fa-fw fa-trash text-red"></i>' . Yii::t('gallery', 'delete'), ['delete', 'id' => $gallery->getId()], [
                'class' => 'btn btn-default btn-sm',
                'data' => [
                    'confirm' => Yii::t('gallery', 'Are you sure you want to delete this item?'),
                    'method' => 'post',
                ],
            ]) ?>
        </div>
    </div>

    <div class="box-body pad">

        <div class="row">
            <div class="col-md-3">
                <?php if ($gallery->hasCoverImage()): ?>
                    <div class="gallery-cover-image">
                        <?= Html::img("{$this->params['gallery.images.source_url']}{$gallery->coverImage->path}", [
                            'class' => 'gallery-cover-image__img',
                        ]) ?>
                    </div>
                <?php endif ?>
            </div>

            <div class="col-md-9">
                <?= DetailView::widget([
                    'model' => $gallery,
                    'template' => "<tr><th width=\"150\">{label}</th><td>{value}</td></tr>",
                    'attributes' => [
                        'gallery_id',
                        [
                            'attribute' => 'user_id',
                            'value' => function ($gallery) {
                                if ($gallery->hasUser()) {
                                    return $gallery->user->username;
                                }

                                return '<span class="not-set">(not set)</span>';
                            },
                        ],
                        'title',
                        'slug',
                        'description:ntext',
                        [
                            'label' => 'Categories',
                            'value' => function ($gallery) {
                                return implode(', ', ArrayHelper::getColumn($gallery->categories, 'title'));
                            },
                        ],
                        'published_at:datetime',
                        [
                            'attribute' => 'orientation',
                            'value' => function ($gallery) {
                                $array = [
                                    1 => 'Straight',
                                    2 => 'Lesbian',
                                    3 => 'Shemale',
                                    4 => 'Gay',
                                ];

                                return $statusNames[$gallery->orientation] ?? '<span class="not-set">(not set)</span>';
                            },
                            'format' => 'html',
                        ],
                        'likes',
                        'dislikes',
                        'images_num',
                        'comments_num',
                        [
                            'attribute' => 'views',
                            'value' => function ($gallery) {
                                return Yii::$app->formatter->asInteger($gallery->views);
                            },
                        ],
                        [
                            'attribute' => 'status',
                            'value' => function ($gallery) use ($statusNames) {
                                return $statusNames[$gallery->status] ?? '<span class="not-set">(unknown)</span>';
                            },
                            'format' => 'html',
                        ],
                        'on_index',
                        'created_at:datetime',
                        'updated_at:datetime',
                    ],
                ]) ?>
            </div>
        </div>

    </div>
</div>

<?php if (!empty($thumbsRotationStats)): ?>
    <div class="box box-default">
        <div class="box-header with-border">
            <h3 class="box-title">Статистика ротации по тумбам</h3>
        </div>

        <div class="box-body pad">
            <table class="table">
                <thead>
                    <tr>
                        <th>Thumb</th>
                        <th>Stats</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($thumbsRotationStats as $item): ?>
                    <tr>
                        <td width="335">
                            <div class="rotation-stats__image">
                                <?php if (!empty($item['image'])): ?>
                                    <?= Html::img($this->params['gallery.images.source_url'] . $item['image']->getPath(), [
                                        'class' => 'rotation-stats__image__img',
                                    ]) ?>
                                <?php endif ?>
                            </div>
                        </td>
                        <td>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th width="150">Category</th>
                                        <th>Ctr</th>
                                        <th>Total shows</th>
                                        <th>Total clicks</th>
                                        <th>Tested</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($item['categories'] as $imageCategory): ?>
                                    <tr>
                                        <td><?= $imageCategory->category->title ?></td>
                                        <td><?= $imageCategory->ctr ?? 0 ?></td>
                                        <td><?= Yii::$app->formatter->asInteger($imageCategory->total_shows) ?></td>
                                        <td><?= Yii::$app->formatter->asInteger($imageCategory->total_clicks) ?></td>
                                        <td><?= $imageCategory->is_tested ? 'Yes' : 'No' ?></td>
                                    </tr>
                                <?php endforeach ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>

                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif ?>

<?php

$css = <<< 'CSS'
    .gallery-cover-image {
        width: 100%;
        height: auto;
        text-align: center;
        overflow: hidden;
    }
    .gallery-cover-image__img {
        display: inline-block;
        max-width: 100%;
        height: auto;
    }
    .rotation-stats__image {
        max-width: 450px;
        max-height: 450px;
    }
    .rotation-stats__image__img {
        display: block;
        max-width: 100%;
        height: auto;
    }
CSS;

$this->registerCss($css);
