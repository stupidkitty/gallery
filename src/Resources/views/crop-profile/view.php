<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\DetailView;

$this->title = Yii::t('gallery', 'crop-profiles');
$this->params['subtitle'] = Yii::t('gallery', 'info');

$this->params['breadcrumbs'][] = ['label' => Yii::t('gallery', 'crop-profiles'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $crop->getName();

?>

<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title"><?= Html::encode($crop->getName()) ?></h3>

        <div class="btn-group pull-right">
            <?= Html::a('<i class="fa fa-fw fa-plus text-green"></i>' . Yii::t('gallery', 'add'), ['create'], ['class' => 'btn btn-default btn-sm']) ?>
            <?= Html::a('<i class="fa fa-fw fa-edit text-blue"></i>' . Yii::t('gallery', 'edit'), ['update', 'id' => $crop->getId()], ['class' => 'btn btn-default btn-sm']) ?>
            <?= Html::a('<i class="fa fa-fw fa-trash text-red"></i>' . Yii::t('gallery', 'delete'), ['delete', 'id' => $crop->getId()], [
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
                <?= DetailView::widget([
                    'model' => $crop,
                    'template' => "<tr><th width=\"150\">{label}</th><td>{value}</td></tr>",
                    'attributes' => [
                        'crop_id',
                        'name:ntext',
                        'comment:ntext',
                        'command:ntext',
                        'created_at:datetime',
                    ],
                ]) ?>
        </div>

    </div>
</div>
