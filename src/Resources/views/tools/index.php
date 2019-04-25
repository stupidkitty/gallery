<?php

use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model yii\base\Model */

$this->title = 'Разное';
$this->params['subtitle'] = 'Галереи';
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="row">
    <div class="col-md-12">

        <div class="box box-default">
            <div class="box-header with-border">
                <i class="fa fa-wrench"></i><h3 class="box-title">Разное</h3>
            </div>

            <div class="box-body pad">

                <table class="table">
                    <tr>
                        <td>
                            <h4>Пересчитать галереи в категориях</h4>
                            <div class="text-muted">В категориях будет произведен подсчет только активных фотосетов.</div>
                        </td>
                        <td style="vertical-align:middle;"><button type="button" class="btn btn-block btn-info" id="recalculate_categories_galleries" data-action="<?= Url::toRoute(['recalculate-categories-galleries'])?>" >Пересчитать</button></td>
                    </tr>

                    <tr>
                        <td>
                            <h4>Установить тумбы для категорий</h4>
                            <div class="text-muted">Тумбы установятся от первых видео на странице категории</div>
                        </td>
                        <td style="vertical-align:middle;"><button type="button" class="btn btn-block btn-success" id="set_categories_thumbs" data-action="<?= Url::toRoute(['set-categories-thumbs']) ?>">Установить тумбы</button></td>
                    </tr>

                    <tr>
                        <td>
                            <h4>Случайные даты публикации</h4>
                            <div class="text-muted">Задать случайную дату в промежутке за последний год по текущую дату.</div>
                        </td>
                        <td style="vertical-align:middle;"><button type="button" class="btn btn-block btn-info" id="random_date" data-action="<?= Url::toRoute(['random-date']) ?>">Задать дату</button></td>
                    </tr>

                    <tr>
                        <td>
                            <h4>Обнуление статистики</h4>
                            <div class="text-muted">Обнулить полностью статистику кликов и показов тумб, категорий. А также просмотры видео, лайки и дизлайки.</div>
                        </td>
                        <td style="vertical-align:middle;"><button type="button" class="btn btn-block btn-warning" id="clear_stats" data-action="<?= Url::toRoute(['clear-stats']) ?>">Обнулить статистику</button></td>
                    </tr>

                    <tr>
                        <td>
                            <h4>Очистить "похожие" видео</h4>
                            <div class="text-muted">"Похожие" галереи будут полностью удалены из базы.</div>
                        </td>
                        <td style="vertical-align:middle;"><button type="button" class="btn btn-block btn-warning" id="clear_related" data-action="<?= Url::toRoute(['clear-related']) ?>">Очистить "похожие"</button></td>
                    </tr>

                    <tr>
                        <td>
                            <h4>Очистить базу видео</h4>
                            <div class="text-muted">Полностью удалить галереи, скриншоты, категории. Также статистику по тумбам, категориям.</div>
                        </td>
                        <td style="vertical-align:middle;"><button type="button" class="btn btn-block btn-danger" id="clear_galleries" data-action="<?= Url::toRoute(['clear-galelries']) ?>">Удалить все галереи</button></td>
                    </tr>
                </table>

            </div>
        </div>

    </div>
</div>

<?php

$js = <<< 'JS'
    (function() {
        $('#recalculate_categories_galleries').click(function(event) {
            event.preventDefault();
            var actionUrl = $(this).data('action');
            var bttn = $(this);

            bttn.prop('disabled', true);

            $.post(actionUrl, function( data ) {
                if (data.error !== undefined) {
                    toastr.error(data.error.message, 'Error');
                } else {
                    toastr.success(data.message, 'Success');
                }
            }, 'json')
            .done(function() {
                bttn.prop('disabled', false);
            });
        });

        $('#set_categories_thumbs').click(function(event) {
            event.preventDefault();
            var bttn = $(this);
            var actionUrl = $(this).data('action');

            bttn.prop('disabled', true);

            $.post(actionUrl, function( data ) {
                if (data.error !== undefined) {
                    toastr.error(data.error.message, 'Error');
                } else {
                    toastr.success(data.message, 'Success');
                }
            }, 'json')
            .done(function() {
                bttn.prop('disabled', false);
            });
        });

        $('#random_date').click(function(event) {
            event.preventDefault();
            var bttn = $(this);
            var actionUrl = $(this).data('action');

            if (confirm('Задать случайную дату у всех галерей в базе??')) {
                bttn.prop('disabled', true);

                $.post(actionUrl, function( data ) {
                    if (data.error !== undefined) {
                        toastr.error(data.error.message, 'Error');
                    } else {
                        toastr.success(data.message, 'Success');
                    }
                }, 'json')
                .done(function() {
                    bttn.prop('disabled', false);
                });
            }
        });

        $('#clear_stats').click(function(event) {
            event.preventDefault();
            var actionUrl = $(this).data('action');

            if (confirm('Обнулить статистику тумб, категорий и галерей?')) {
                $.post(actionUrl, function( data ) {
                    if (data.error !== undefined) {
                        toastr.error(data.error.message, 'Error');
                    } else {
                        toastr.success(data.message, 'Success');
                    }
                }, 'json');
            }
        });

        $('#clear_related').click(function(event) {
            event.preventDefault();
            var actionUrl = $(this).data('action');

            if (confirm('Очистить "похожие галереи"?')) {
                $.post(actionUrl, function( data ) {
                    if (data.error !== undefined) {
                        toastr.error(data.error.message, 'Error');
                    } else {
                        toastr.success(data.message, 'Success');
                    }
                }, 'json');
            }
        });

        $('#clear_galleries').click(function(event) {
            event.preventDefault();
            var actionUrl = $(this).data('action');
            var confirmed = prompt('Для полного удаления галерей напишите слово DELETE', '');

            if (confirmed != null && confirmed === 'DELETE') {
                bttn.prop('disabled', true);

                $.post(actionUrl, function( data ) {
                    if (data.error !== undefined) {
                        toastr.error(data.error.message, 'Error');
                    } else {
                        toastr.success(data.message, 'Success');
                    }
                }, 'json')
                .done(function() {
                    bttn.prop('disabled', false);
                });
            }
        });
    })();
JS;

$this->registerJS($js, View::POS_END);

?>
