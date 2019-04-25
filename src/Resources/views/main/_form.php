<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use RS\Component\Core\Widget\Select2;
use RS\Component\Core\Widget\DateTimePicker;

?>

    <?php $activeForm = ActiveForm::begin([
        'id' => 'gallery-form',
        'options' => [
            'enctype' => 'multipart/form-data',
        ],
    ]) ?>

    <div class="form-group required">
        <label class="control-label" for="title"><?= Yii::t('gallery', 'title') ?></label>
        
        <?= Html::activeTextInput($form, 'title', ['class' => 'form-control count-letters', 'required' => true]) ?>
    </div>

    <?= $activeForm->field($form, 'slug')->textInput()->hint('Оставить пустым, чтобы сгенерировать заново (транслит названия)') ?>

    <div class="form-group">
        <label class="control-label" for="description"><?= Yii::t('gallery', 'description') ?></label>
        
        <?= Html::activeTextarea($form, 'description', ['class' => 'form-control count-letters', 'rows' => 5]) ?>
    </div>


    <?= $activeForm->field($form, 'categories_ids')->widget(Select2::class, [
        'items' => $categoriesNames,
        'autoSort' => false,
        'clientOptions' => [
            'minimumResultsForSearch' => -1,
            'placeholder' => 'Выберите категории',
            'allowClear' => true,
        ],
        'options' => [
            'multiple' => true,
            'class' => 'form-control',
        ],
    ]) ?>

    <div class="row">
        <div class="col-md-3">
            <?= $activeForm->field($form, 'published_at')->widget(
                    DateTimePicker::class,
                    [
                        'clientOptions' => [
                            'format' => 'YYYY-MM-DD HH:mm:ss',
                            'locale' => 'ru',
                            'sideBySide' => true
                        ],
                        'containerOptions' => [
                            'style' => 'max-width: 300px;',
                        ],
                    ]
                )
                ->label('Время публикации')
                ->hint('Y-m-d H:i:s (eg. ' . gmdate('Y-m-d H:i:s') . ')');
            ?>
        </div>
        <div class="col-md-9">
            <?= $activeForm->field($form, 'published_at_method')->radioList([
                'dont-set' => 'Не устанавливать (не изменять)',
                'current' => 'Текущая (указанная)',
                'interval' => 'Автоматически, с интевалом от крайнего опубликованного',
            ], [
                'separator' => '<br>',
            ]) ?>
        </div>
    </div>

    <?= $activeForm->field($form, 'user_id')->dropDownList($userNames, [
        'style' => 'width:initial;',
    ]) ?>

    <?= $activeForm->field($form, 'orientation')->dropDownList([
        1 => 'Straight',
        2 => 'Lesbian',
        3 => 'Shemale',
        4 => 'Gay',
    ], [
        'prompt' => '-- Выбрать --',
        'style' => 'width:initial;',
    ]) ?>

    <?= $activeForm->field($form, 'status')->dropDownList($statusNames, [
        'style' => 'width:initial;',
    ]) ?>

    <?= $activeForm->field($form, 'template')->textInput() ?>

    <?= $activeForm->field($form, 'on_index')->checkbox() ?>

    <?= $activeForm->field($form, 'images[]')
        ->fileInput(['multiple' => true, 'accept' => 'image/*'])
        ->label('Прикрепленные файлы')
    ?>

    <?= Html::activeHiddenInput($form, 'image_id') ?>

    <?php ActiveForm::end() ?>

<script>
    var LetterCounter = {
        inputs: {},
        events: ['keyup', 'paste', 'input'],
        init: function (selectors, options = {}) {
            this.inputs = document.querySelectorAll(selectors);
            //console.log(NodeList.prototype.isPrototypeOf(this.inputs));

            this.inputs.forEach((elInput) => {
                this.events.forEach((eventType) => {
                    elInput.addEventListener(eventType, (event) => {
                        this.handleText(event.target);
                    });
                });
            });
        },
        prepareString: function (str) {
            str = str.trim().replace(/[ ]{2,}/gi,' ');
            str = str.replace(/ \n/, "\n");
            str = str.replace(/\n /, "\n");

            return str;
        },
        handleText: function (el) {
            let textVal = el.getAttribute('value') || el.innerText || el.textContent;//elInput.getAttribute('value');

            textVal = this.prepareString(textVal.toString());
            console.log(textVal);
        },
    };
    LetterCounter.init('.count-letters');
    /*let watchCountLettersInputs = document.querySelectorAll('.count-letters');
    
    watchCountLettersInputs.forEach(function (elInput) {
        let textVal = elInput.getAttribute('value') || elInput.innerText || elInput.textContent;//elInput.getAttribute('value');
        console.log(textVal.toString());
    });*/
</script>