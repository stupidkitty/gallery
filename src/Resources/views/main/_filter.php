<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use RS\Component\Core\Widget\Select2;

if (!empty($filterForm->galleries_ids)) {
    $filterForm->galleries_ids = implode(',', $filterForm->galleries_ids);
}

?>

<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Фильтр</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
            <button type="button" class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
        </div>
    </div>

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <div class="box-body">

        <div class="row show-grid">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="gallery-search-title">Название, описание</label>
                    <?= Html::activeInput('text', $filterForm, 'title', ['class' => 'form-control']) ?>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="gallery-search-galleries_ids">ID (через запятую)</label>
                    <?= Html::activeInput('text', $filterForm, 'galleries_ids', ['class' => 'form-control']) ?>
                </div>
            </div>
        </div>

        <div class="row show-grid">
            <div class="col-md-2">
                <div class="form-group">
                    <label for="gallery-search-user_id">Автор</label>
                    <?= Html::activeDropDownList($filterForm, 'user_id', $userNames, ['class' => 'form-control', 'prompt' => '-- Любой --']) ?>
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    <label for="gallery-search-status">Статус</label>
                    <?= Html::activeDropDownList($filterForm, 'status', $statusNames, ['class' => 'form-control', 'prompt' => '-- Любой --']) ?>
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    <label for="gallery-search-status">Категория</label>
                    <?= Html::activeDropDownList($filterForm, 'category_id', $categoriesNames, ['class' => 'form-control', 'prompt' => '-- Все --']) ?>
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    <label for="gallery-search-per_page">Per page</label>
                    <?= Html::activeInput('text', $filterForm, 'per_page', ['class' => 'form-control']) ?>
                </div>
            </div>
        </div>

    </div>

    <div class="box-footer">
        <div class="col-md-3 col-md-offset-1">
            <div class="form-group">
                <?= Html::submitButton('Применить', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Сброс', ['index'], ['class' => 'btn btn-default']) ?>
            </div>
        </div>
    </div>
    <?php ActiveForm::end() ?>
</div>
