<?php

use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use RS\Module\AdminModule\Asset\DatetimePicker;

DatetimePicker::register($this);

$this->title = 'Импорт';
$this->params['subtitle'] = 'Галереи';

$this->params['breadcrumbs'][] = ['label' => 'Импорт', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Галереи';

?>

<div class="box box-primary">
    <div class="box-header with-border">
        <i class="glyphicon glyphicon-import text-light-violet"></i><h3 class="box-title">Импорт видео</h3>
        <div class="box-tools pull-right">
            <div class="btn-group">
                <?= Html::beginForm(['galleries'], 'get', [
                        'name' => 'preset-select'
                    ]); ?>
                    Настройки импорта: <?= Html::dropDownList('preset', $preset, $presetNames, [
                        'prompt' => [
                            'text' => 'Default',
                            'options' => ['value' => 0]
                        ],
                        'id' => 'preset',
                        'class' => 'btn-default btn-sm',
                    ]) ?>
                <?= Html::endForm() ?>
            </div>
            <div class="btn-group">
                <?= Html::a('<i class="fa fa-plus text-green"></i> Add', ['add-feed'], ['class' => 'btn btn-default btn-sm', 'title' => 'Добавить новый фид']) ?>
                <?php if ($preset > 0): ?>
                    <?= Html::a('<i class="fa fa-edit text-blue"></i> Edit', ['update-feed', 'id' => $preset], ['class' => 'btn btn-default btn-sm', 'title' => 'Редактировать фид']) ?>
                    <?= Html::a('<i class="fa fa-trash-o text-red"></i> Delete', ['delete-feed', 'id' => $preset], [
                        'class' => 'btn btn-default btn-sm',
                        'title' => 'Удалить фид',
                        'data' => [
                            'confirm' => 'Действительно хотите удалить этот фид?',
                            'method' => 'post',
                        ],
                    ]) ?>
                <?php endif ?>
            </div>
        </div>
    </div>


    <div class="box-body pad">
        <?= Html::beginForm(['galleries'], 'post', [
            'id' => 'gallery-import-form',
            'enctype' => 'multipart/form-data'
        ]) ?>
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
                    <?= Html::activeInput('text', $form, 'delimiter', ['class' => 'form-control']) ?>
                </div>

                <div class="col-md-2 form-group">
                    <label class="control-label">Ограничитель поля</label>
                    <?= Html::activeInput('text', $form, 'enclosure', ['class' => 'form-control']) ?>
                </div>
            </div>


            <h4>Поля csv</h4>
            <div class="row csv-fields">
                <?php foreach ($form->fields as $field): ?>
                    <div class="col-md-2 form-group">
                        <?= Html::dropDownList('fields[]', $field, $form->getOptions(), ['class' => 'form-control']) ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row">
                <div class="col-md-12 form-group">
                    <label for="csv_file" class="control-label">Файл импорта</label>
                    <?= Html::fileInput('csv_file', null, ['id' => 'csv_file']) ?>
                    <p class="help-block">Убедитесь в соответствии полей файла с текущими настройками.</p>
                </div>
            </div>

            <h3>Дополнительные настройки</h3>

            <div class="row">
                <div class="col-md-3 form-group">
                    <label class="control-label">Время публикации</label>
                    <div class="input-group date" id="default_date">
                        <?= Html::activeInput('text', $form, 'default_date', ['class' => 'form-control']) ?>
                        <span class="input-group-addon">
                            <span class="glyphicon glyphicon-calendar"></span>
                        </span>
                    </div>
                    <div class="help-block">Y-m-d H:i:s (eg. 2018-01-16 14:04:46)</div>
                </div>
                <div class="col-md-9 form-group">
                    <fieldset id="set_published_at_method">
                        <?php echo Html::activeRadioList($form, 'set_published_at_method', [
                                'now' => 'Текущая (указанная)',
                                'auto_add' => 'Автоматически добавлять с интервалом от крайнего опубликованного поста',
                            ],
                            [
                                'item' => function ($index, $label, $name, $checked, $value) {
                                    $radio = Html::radio($name, $checked, ['value' => $value]);
                                    $span = Html::tag('span', $label, ['class' => 'radio-item__label']);
                                    $label = Html::label("{$radio} {$span}", null, ['class' => 'radio-item radio-item--flex']);

                                    return $label;
                                },
                                'label' => false,
                            ]
                        ) ?>
                    </fieldset>
                    <div class="help-block">Если в CSV указана дата публикации, то эти настройки будут игнорироваться (применится дата публикации из CSV)</div>
                </div>

                <div class="col-md-12 form-group">
                    <label class="control-label">Пользователь</label>
                    <?= Html::activeDropDownList($form, 'user_id', $userNames, ['class' => 'form-control', 'style' => 'width:initial;']) ?>
                </div>
                <div class="col-md-12 form-group">
                    <label class="control-label">Статус</label>
                    <?= Html::activeDropDownList($form, 'status', $statusNames, ['class' => 'form-control', 'style' => 'width:initial;']) ?>
                </div>

                <div class="col-md-12 form-group">
                    <label class="control-label">Шаблон для просмотра (по умолчанию используется view)</label>
                    <?= Html::activeInput('text', $form, 'template', ['class' => 'form-control', 'style' => 'width:200px']) ?>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-12 form-group">
                    <label class="checkbox-block"><?= Html::activeCheckbox($form, 'skip_first_line', ['label' => false]) ?> <span>Пропустить первую строчку</span></label>
                    <div class="help-block">Активировать, если в первой строке указаны названия столбцов</div>
                </div>

                <div class="col-md-12 form-group">
                    <label class="checkbox-block"><?= Html::activeCheckbox($form, 'skip_duplicate_urls', ['label' => false]) ?> <span>Пропускать галереи с повторяющимися source URL-ами (если такие имеются)</span></label><br>
                </div>

                <div class="col-md-12 form-group">
                    <label class="checkbox-block"><?= Html::activeCheckbox($form, 'skip_new_categories', ['label' => false]) ?> <span>Запретить создание новых категорий</span></label>
                </div>
                <div class="col-md-12 form-group">
                    <label class="checkbox-block"><?= Html::activeCheckbox($form, 'external_images', ['label' => false]) ?> <span>Использовать внешние фото и тумбы (не будут скачиваться и нарезаться)</span></label>
                </div>


            </div>

        <?= Html::endForm() ?>
        <progress style="display: none;width: 300px;"></progress>
    </div>


    <div class="box-footer clearfix">
        <div class="form-group">
            <?= Html::submitButton('Добавить', ['class' => 'btn btn-default', 'form' => 'gallery-import-form']) ?>
            <?= Html::a('Фиды', ['list-feeds'], ['class' => 'btn btn-warning']) ?>
        </div>
    </div>

</div>

<?php

$rowOptions = [];
foreach ($form->getOptions() as $key => $val) {
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
            name: 'fields[]'
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

    $('#preset').on('change', function() {
        document.forms['preset-select'].submit();
    });

    $('#default_date').datetimepicker({
        format: 'YYYY-MM-DD HH:mm:ss',
        locale: 'ru',
        sideBySide: true
    });

    /*var importForm = document.querySelector('#gallery-import-form');

    importForm.addEventListener('submit', function (e) {
        this.onsubmit = function (){return false};
        e.preventDefault();

        var formData = new FormData(this);
        formData.append('file', this.csv_file.files[0]);


        $('progress').toggle();

        $.ajax({
          method: 'POST',
          cache: false,
          processData: false,
          contentType: false,
          enctype: 'multipart/form-data',
          url: this.action,
          data: formData,
          // Custom XMLHttpRequest
          xhr: function() {
              var myXhr = $.ajaxSettings.xhr();
              if (myXhr.upload) {
                  // For handling the progress of the upload
                  myXhr.upload.addEventListener('progress', function(e) {
                      if (e.lengthComputable) {
                          $('progress').attr({
                              value: e.loaded,
                              max: e.total,
                          });
                      }
                  } , false);
              }
              return myXhr;
          }
        }).done(function() {
            $('progress').toggle();
        });
    });*/
JS;

$this->registerJS($js);
