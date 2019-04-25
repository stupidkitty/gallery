<?php

use yii\helpers\Url;
use yii\helpers\Html;

$this->title = Yii::t('gallery', 'galleries');
$this->params['subtitle'] = Yii::t('gallery', 'create');

$this->params['breadcrumbs'][] = ['label' => Yii::t('gallery', 'galleries'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('gallery', 'create');

?>

<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title"><?= Html::encode($this->title) ?></h3>
    </div>

    <div class="box-body pad">
        <?= $this->render('_form', [
            'form' => $form,
            'categoriesNames' => $categoriesNames,
            'userNames' => $userNames,
            'statusNames' => $statusNames,
        ]) ?>
    </div>

    <div class="box-footer clearfix">
        <div class="row">
            <div class="col-md-2 col-md-offset-4">
                <?= Html::submitButton('<i class="fa fa-fw fa-plus text-green"></i>' . Yii::t('gallery', 'add'), ['class' => 'btn btn-default', 'form' => 'gallery-form']) ?>
                <?= Html::a('<i class="fa fa-fw fa-reply text-orange"></i>' . Yii::t('gallery', 'back'), ['index'], ['class' => 'btn btn-default'])?>
            </div>
        </div>
    </div>

</div>
