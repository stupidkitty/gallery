<?php

use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Html;

$this->title = Yii::t('gallery', 'galleries');
$this->params['subtitle'] = Yii::t('gallery', 'update');

$this->params['breadcrumbs'][] = ['label' => Yii::t('gallery', 'galleries'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('gallery', 'update');

$coverImageUrl = $gallery->hasCoverImage() ? $this->params['gallery.images.source_url'] . $gallery->coverImage->getPath() : '';

?>

<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title"><?= Html::encode($gallery->getTitle()) ?></h3>

        <div class="btn-group pull-right">
            <?= Html::a('<i class="fa fa-fw fa-plus text-green"></i>' . Yii::t('gallery', 'add'), ['create'], ['class' => 'btn btn-default btn-sm']) ?>
            <?= Html::a('<i class="fa fa-fw fa-info-circle text-blue"></i>' . Yii::t('gallery', 'info'), ['view', 'id' => $gallery->getId()], ['class' => 'btn btn-default btn-sm']) ?>
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
                <div class="gallery-cover-image">
					<?= Html::img($coverImageUrl, [
						'class' => 'gallery-cover-image__img',
						'id' => 'cover-image',
					]) ?>
				</div>
            </div>

            <div class="col-md-9">
                <?= $this->render('_form', [
                    'form' => $form,
                    'categoriesNames' => $categoriesNames,
                    'userNames' => $userNames,
                    'statusNames' => $statusNames,
                ]) ?>
            </div>
        </div>
    </div>

    <div class="box-footer">
        <div class="row">
            <div class="col-md-2 col-md-offset-4">
                <?= Html::submitButton('<i class="fa fa-fw fa-check text-green"></i>' . Yii::t('gallery', 'save'), ['class' => 'btn btn-default', 'form' => 'gallery-form']) ?>
                <?= Html::a('<i class="fa fa-arrow-left"></i> ' . Yii::t('gallery', 'back'), ['index'], ['class' => 'btn btn-warning']) ?>
            </div>
        </div>
    </div>
</div>

<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title">Изображения</h3>
    </div>

    <div class="box-body pad">
        <?php if ($gallery->hasImages()): ?>
            <div id="image-list" class="gallery__list">

                <?php foreach ($gallery->images as $image): ?>
                    <div class="gallery__card<?= $image->isEnabled() ? '' : ' disabled' ?>" data-key="<?= $image->getId() ?>">
                        <div class="gallery__card__image">
                            <?= Html::img("{$this->params['gallery.images.source_url']}{$image->path}", ['class' => 'gallery__card__img']) ?>
                        </div>
                        <div class="gallery__card__info">ID: <?= $image->getId() ?>
                            <div class="pull-right">
                                <a href="<?= $this->params['gallery.images.source_url'] . $image->path ?>" target="_blank">full</a>
                            </div>
                        </div>
                        <div class="gallery__card__actions btn-group">
                            <?= Html::button('Set Main', [
                                'class' => 'btn btn-sm btn-default',
                                'data-url' => Url::toRoute(['set-cover-image']),
                                'data-type' => 'submit',
                                'data-action' => 'set-cover',
                                'data-image_id' => $image->getId(),
                            ]) ?>
                            <?= Html::button(
                                $image->isEnabled() ? '<i class="glyphicon glyphicon-eye-open"></i>' : '<i class="glyphicon glyphicon-eye-close text-red"></i>',
                                [
                                    'class' => 'btn btn-sm btn-default',
									'data-url' => Url::toRoute(['image-toggle-enabled']),
									'data-type' => 'submit',
									'data-action' => 'toggle-enabled',
                                    'data-image_id' => $image->getId(),
                                ]
                            ) ?>
                            <?= Html::button('<i class="fa fa-trash text-red"></i>', [
                                'class' => 'btn btn-sm btn-default',
                                'data-url' => Url::toRoute(['delete-image']),
                                'data-type' => 'submit',
                                'data-action' => 'delete',
                                'data-image_id' => $image->getId(),
                                'data-confirm' => 'Are you sure?',
                            ]) ?>
                        </div>
                    </div>
                <?php endforeach ?>

            </div>
        <?php else: ?>
            Нет фотографий
        <?php endif ?>
    </div>

    <div class="box-footer with-border">
		<?= Html::a('<span class="glyphicon glyphicon-save text-blue"></span> Сохранить порядок сортировки',
			['save-order', 'id' => $gallery->getId()],
			[
				'id' => 'images-order-save',
				'class' => 'btn btn-sm btn-default',
			]
		) ?>
	</div>
</div>

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

    .gallery__list {
        display: flex;
        flex-wrap: wrap;
    }
    .gallery__card {
        margin: 5px;
        border: 1px solid #ccc;
        border-radius: 3px;
        overflow: hidden;
    }
    .gallery__card.disabled {
        background: #ffd4d4;
    }
    .gallery__card__image {
        width: 300px;
        height: 300px;
        max-height: 300px;
        display: flex;
        justify-content: center;
    }
    .gallery__card__img {
        display: block;
        max-width: 100%;
        max-height: 100%;
        align-self: center;
    }
    .gallery__card__info {
        padding: 5px;
    }
    .gallery__card__actions {
        padding: 5px;
    }
    .gallery__card__placeholder {
		margin: 5px;
		background: #fafafa;
		border: 1px dashed #ccc;
	}
CSS;

$js = <<< 'JS'
	$("#image-list").sortable({
	   start: function(e,ui){
	        ui.placeholder.height(ui.item.height()); // margin bottom 15
	        ui.placeholder.width(ui.item.width());
    	},
		placeholder: 'sortable-placeholder gallery__card__placeholder',
		cursor: 'move',
	});

	let imagesOrderSaveBtn = document.querySelector('#images-order-save');

    imagesOrderSaveBtn.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        let cardList = document.querySelectorAll('.gallery__card');
        let actionUrl = event.target.getAttribute('href');
        let formData = new FormData();

        cardList.forEach(function (card) {
            if (card.hasAttribute('data-key')) {
                let idVal = parseInt(card.getAttribute('data-key'), 10);

                if (NaN !== idVal) {
                    formData.append('order[]', idVal);
                }
            }
        });

        fetch(actionUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then((response) => {
            if (!response.ok) {
                throw new Error(response.statusText);
            }

            return response;
        })
        .then(response => response.json())
        .then(function (response) {
            if (undefined !== response.error) {
                throw new Error(response.error.message);
            }

            toastr.success(response.message);
        })
        .catch(function(error) {
            toastr.error(error.message);
        });
    });

    let imageList = document.querySelector('#image-list');
	let cardButtons = imageList.querySelectorAll('button[data-type="submit"]');

	cardButtons.forEach(function (cardButton) {
		cardButton.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            let imageId = this.getAttribute('data-image_id');
            let actionUrl = this.getAttribute('data-url');
			let formData = new FormData();
			let imageCard = imageList.querySelector('div[data-key="'+imageId+'"]');
			let clickedButton = this;

            formData.append('gallery_id', galleryId);
            formData.append('image_id', imageId);

            if (this.hasAttribute('data-confirm')) {
                let result = confirm(this.getAttribute('data-confirm'));

                if (!result) {
                    return;
                }
			}

			fetch(actionUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin'
			})
			.then((response) => {
				if (!response.ok) {
					throw new Error(response.statusText);
				}

				return response;
			})
			.then(response => response.json())
			.then(function (response) {
				if (undefined !== response.error) {
					throw new Error(response.error.message);
				}

				let action = '';
				if (clickedButton.hasAttribute('data-action')) {
					action = clickedButton.getAttribute('data-action');
				}

				if ('toggle-enabled' === action) {
					if (true === response.enabled) {
						imageCard.classList.remove('disabled');
					} else if (false === response.enabled) {
						imageCard.classList.add('disabled');
					}
				} else if ('delete' === action) {
					imageCard.remove();
				} else if ('set-cover' === action) {
					let coverImage = document.querySelector('#cover-image');
					let imageUrl = imageCard.querySelector('img.gallery__card__img').getAttribute('src');

					coverImage.setAttribute('src', imageUrl);
				}

				toastr.success(response.message);
			})
			.catch(function(error) {
				toastr.error(error.message);
			});
		});
	});
JS;

$this->registerCss($css);
$this->registerJsVar('galleryId', $gallery->getId(), View::POS_HEAD);
$this->registerJs($js, View::POS_END);
