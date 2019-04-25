<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\web\View;

/* @var $this yii\web\View */

$this->title = 'Импорт';
$this->params['subtitle'] = 'Добавить настройку импорта галереи';

$this->params['breadcrumbs'][] = ['label' => 'Импорт', 'url' => ['galleries']];
$this->params['breadcrumbs'][] = ['label' => 'Фиды', 'url' => ['list-feeds']];
$this->params['breadcrumbs'][] = 'Новый';

?>

<div class="row">
	<div class="col-md-12">

		<div class="box box-success">
			<div class="box-header with-border">
				<i class="fa fa-file-o text-purple-disabled"></i><h3 class="box-title">Добавить настройку импорта галереи</h3>
            </div>

			<?php $form = ActiveForm::begin(); ?>

	            <div class="box-body pad">

				    <?= $form->field($feed, 'name')->textInput(['maxlength' => true]) ?>

				    <?= $form->field($feed, 'description')->textInput(['maxlength' => true]) ?>

					<h4>Настройки ввода</h4>
					<div class="row">
						<div class="col-md-3 form-group">
							<label class="control-label" style="display:block;">Добавить\удалить поля</label>
							<div class="btn-group">
                    			<button type="button" id="add_field" class="btn btn-default"><i class="fa fa-plus"></i></button>
                    			<button type="button" id="remove_field" class="btn btn-default"><i class="fa fa-minus"></i></button>
                    		</div>
						</div>
						<div class="col-md-2 form-group">
							<label class="control-label">Разделитель</label>
							<?= Html::activeInput('text', $feed, 'delimiter', ['class' => 'form-control']) ?>
            			</div>
						<div class="col-md-2 form-group">
							<label class="control-label">Ограничитель поля</label>
							<?= Html::activeInput('text', $feed, 'enclosure', ['class' => 'form-control']) ?>
            			</div>
					</div>

					<h4>Поля csv</h4>
					<div class="row csv-fields">
						<?php foreach ($feed->fields as $field): ?>
							<div class="col-md-2 form-group">
								<?= Html::dropDownList('ImportFeed[fields][]', '', $feed->getFieldsOptions(), ['class' => 'form-control']) ?>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="row">
						<div class="col-md-12 form-group">
							<label class="checkbox-block"><?= Html::activeCheckbox($feed, 'skip_first_line', ['label' => false]) ?> <span>Пропустить первую строчку</span></label>
							<div class="help-block">Активировать, если в первой строке указаны названия столбцов</div>
						</div>
                        <div class="col-md-12 form-group">
							<label class="checkbox-block"><?= Html::activeCheckbox($feed, 'skip_duplicate_urls', ['label' => false]) ?> <span>Пропускать галереи с повторяющимися source URL-ами (если такие имеются)</span></label><br>
						</div>

						<div class="col-md-12 form-group">
							<label class="checkbox-block"><?= Html::activeCheckbox($feed, 'skip_new_categories', ['label' => false]) ?> <span>Запретить создание новых категорий</span></label>
						</div>

						<div class="col-md-12 form-group">
							<label class="checkbox-block"><?= Html::activeCheckbox($feed, 'external_images', ['label' => false]) ?> <span>Использовать внешние фото и тумбы (не будут скачиваться и нарезаться)</span></label>
						</div>

						<div class="col-md-12 form-group">
							<label class="control-label">Шаблон для галереи (по умолчанию используется view)</label>
							<?= Html::activeInput('text', $feed, 'template', ['class' => 'form-control', 'style' => 'width:200px']) ?>
            			</div>
					</div>

				</div>


				<div class="box-footer clearfix">
				    <div class="form-group">
						<?= Html::submitButton('Добавить', ['class' => 'btn btn-success']) ?>
						<?= Html::a('К списку', ['list-feeds'], ['class' => 'btn btn-warning']) ?>
					</div>
				</div>

			<?php ActiveForm::end(); ?>

		</div>

	</div>
</div>

<?php

$rowOptions = [];
foreach ($feed->getFieldsOptions() as $key => $val) {
	$rowOptions[] = [
		'value' => $key,
		'text' => $val,
	];
}

$encodedOptions = json_encode($rowOptions);
$this->registerJS("var csvSelectOptions = {$encodedOptions};", View::POS_HEAD, 'csvSelectOptions');

$js = <<< 'JS'
	$('#add_field').click(function() {
		var tagDiv = $('<div/>', {
		    class: 'col-md-2 form-group'
		});
		var tagSelect = $('<select/>', {
		    class: 'form-control',
		    name: 'ImportFeed[fields][]'
		});

		$(csvSelectOptions).each(function() {
			tagSelect.append($('<option>').attr('value',this.value).text(this.text));
		});

		tagSelect.appendTo(tagDiv);
		tagDiv.appendTo('.csv-fields');
	});
	$('#remove_field').click(function(){
		var fields_container = $('.csv-fields div');
		var childs_num = fields_container.children().length;

		if (childs_num > 1) {
			fields_container.last().remove();
		}
	});
JS;

$this->registerJS($js);
?>
