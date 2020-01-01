<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = Yii::t('gallery', 'galleries');
$this->params['subtitle'] = Yii::t('gallery', 'edit');

$this->params['breadcrumbs'][] = [
    'label' => Yii::t('gallery', 'galleries'),
    'url' => ['main/index'],
];
$this->params['breadcrumbs'][] = ['label' => Yii::t('gallery', 'categories'), 'url' => ['create']];
$this->params['breadcrumbs'][] = Yii::t('gallery', 'edit');

?>

<div class="row">

	<div class="col-md-4">
		<?= $this->render('_left_sidebar', [
			'categories' => $categories,
			'active_id' => isset($category)? $category->getId() : 0,
		]) ?>
	</div>

	<div class="col-md-8">

		<div class="box box-primary">
			<div class="box-header with-border">
				<i class="fa fa-edit"></i><h3 class="box-title">Редактирование: <?= $category->title ?></h3>
				<div class="box-tools pull-right">
					<div class="btn-group">
						<?= Html::a('<i class="glyphicon glyphicon-import" style="color:#ad00ff;"></i> ' . Yii::t('gallery', 'import'), ['import/categories'], ['class' => 'btn btn-default btn-sm', 'title' => 'Импорт категорий']) ?>
						<?= Html::a('<i class="fa fa-fw fa-plus text-green"></i>' . Yii::t('gallery', 'add'), ['create'], ['class' => 'btn btn-default btn-sm', 'title' => 'Добавить категорию']) ?>
						<?= Html::a('<i class="fa fa-fw fa-info-circle text-blue"></i>' . Yii::t('gallery', 'info'), ['view', 'id' => $category->getId()], ['class' => 'btn btn-default btn-sm', 'title' => 'Информация о категории']) ?>
						<?= Html::a('<i class="fa fa-fw fa-trash-o text-red"></i>' . Yii::t('gallery', 'delete'), ['delete', 'id' => $category->getId()], [
				            'class' => 'btn btn-default btn-sm',
				            'title' => 'Удалить категорию',
				            'data' => [
				                'confirm' => 'Действительно хотите удалить эту категорию?',
				                'method' => 'post',
				            ],
				        ]) ?>
					</div>
				</div>
            </div>

	        <div class="box-body pad">
                <?php $activeForm = ActiveForm::begin([
                    'id' => 'category-form',
                    'method' => 'POST',
                    'action' => ['update', 'id' => $category->getId()],
                ]) ?>

                    <?php echo $this->render('_form_fields', [
                        'form' => $form,
                        'activeForm' => $activeForm,
                    ]) ?>

                <?php ActiveForm::end() ?>

			</div>


			<div class="box-footer clearfix">
			    <div class="form-group">
					<?= Html::submitButton('<i class="fa fa-fw fa-check text-green"></i>' . Yii::t('gallery', 'save'), ['class' => 'btn btn-default', 'form' => 'category-form']) ?>
				</div>
			</div>

		</div>

	</div>
</div>
