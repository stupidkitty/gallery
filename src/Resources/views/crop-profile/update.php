<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = Yii::t('gallery', 'crop-profiles');
$this->params['subtitle'] = Yii::t('gallery', 'update');

$this->params['breadcrumbs'][] = ['label' => Yii::t('gallery', 'crop-profiles'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('gallery', 'update');

?>

<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title"><?= Html::encode($crop->getName()) ?></h3>

        <div class="btn-group pull-right">
            <?= Html::a('<i class="fa fa-fw fa-plus text-green"></i>' . Yii::t('gallery', 'add'), ['create'], ['class' => 'btn btn-default btn-sm']) ?>
            <?= Html::a('<i class="fa fa-fw fa-info-circle text-blue"></i>' . Yii::t('gallery', 'info'), ['view', 'id' => $crop->getId()], ['class' => 'btn btn-default btn-sm']) ?>
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
        <?= $this->render('_form', [
            'action' => ['update', 'id' => $crop->getId()],
            'form' => $form,
        ]) ?>
    </div>

    <div class="box-footer">
        <div class="row">
            <div class="col-md-2 col-md-offset-4">
                <?= Html::submitButton('<i class="fa fa-fw fa-check text-green"></i>' . Yii::t('gallery', 'save'), ['class' => 'btn btn-default', 'form' => 'crop-form']) ?>
                <?= Html::a('<i class="fa fa-arrow-left"></i> ' . Yii::t('gallery', 'back'), ['index'], ['class' => 'btn btn-warning']) ?>
            </div>
        </div>
    </div>
</div>
