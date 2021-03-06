<?php
namespace SK\GalleryModule\Command;

use Yii;
use yii\console\Controller;
use SK\GalleryModule\Model\Image;
use SK\GalleryModule\Queue\CreateThumbsJob;

/**
 * This command echoes the first argument that you have entered.
 */
class ImageController extends Controller
{
    /**
     * Ставит в задачу создание новых тумб. (можно пересоздать текущие)
     */
    public function actionCreateThumbs()
    {
        $query = Image::find()
            ->select(['image_id']);

        foreach ($query->batch(200) as $images) {
            foreach ($images as $image) {
                Yii::$app->queue
                    ->push(new CreateThumbsJob([
                        'image_id' => $image->getId(),
                    ]));
            }
        }
    }
}
