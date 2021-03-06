<?php
namespace SK\GalleryModule\Service;

use Yii;
use SK\GalleryModule\Model\RotationStats;
use RS\Component\Core\Settings\SettingsInterface;

class Rotator
{
    /**
     * @var integer Default test item period (test shows).
     */
    const TEST_ITEM_PERIOD = 200;

    /**
     * @var int default recalculate ctr period (shows);
     */
    const RECALCULATE_CTR_PERIOD = 2000;

    /**
     * Устанавливает флаг "тестировано" у записи
     *
     * @return void
     */
    public function markAsTestedRows()
    {
        $settings = Yii::$container->get(SettingsInterface::class);

        $test_item_period = (int) $settings->get('test_item_period', static::TEST_ITEM_PERIOD, 'gallery');

            // Завершим тестовый период у тумб, если набралась необходимая статистика.
        $query = RotationStats::find()
            ->select(['category_id', 'gallery_id', 'image_id', 'is_tested'])
            ->where(['is_tested' => 0])
            ->andWhere(['>=', 'total_shows', $test_item_period]);

        foreach ($query->batch(50) as $rows) {
            foreach ($rows as $row) {
                $row->is_tested = 1;
                $row->save();
            }
        }

        /**
         * Для нескольких тумб: выбрать все видео. Затем проверить есть ли у текущего фото еще не закончившие тест.
         * Если все тумбы у видео закончили тест, то проверим, если ли у видео другие тумбы. Если есть, то начнем тестировать их.
         * Для этого снимем флажок "лучшая тумба" и переведем его на новую.
         * После того, как закончатся все тумбы (проверим, если нетестированные еще) Выберем лучшую тумбу из всех имеющихся по цтр
         * и выставим у нее флажок "лучшая тумба".
         */
    }

    /**
     * Метод смещает контрольные точки у тумб. Всего контрольных точек пять.
     * Значит берем клики периода рекалькуляции цтр и раскидываем равномерно по пяти точкам.
     * Затем выберем все тумбы, которые достигли необходимого значения, обнулим счетчик и сместим вправо на следующую точку.
     *
     * @return void
     */
    public function shiftHistoryCheckpoint()
    {
        $settings = Yii::$container->get(SettingsInterface::class);

        $recalculate_ctr_period = $settings->get('recalculate_ctr_period', static::RECALCULATE_CTR_PERIOD, 'gallery');
        $showsCheckpointValue = (int) ceil($recalculate_ctr_period / 5);

        $thumbStats = RotationStats::find()
            ->select(['gallery_id', 'category_id', 'image_id', 'current_shows', 'current_clicks', 'current_index'])
            ->where(['>=', 'current_shows', $showsCheckpointValue])
            ->asArray()
            ->all();

        if (empty($thumbStats)) {
            return;
        }

        foreach ($thumbStats as $thumbStat) {
            $currentIndex = (int) $thumbStat['current_index'];

            if ($currentIndex == 4) {
                $currentIndex = 0;
            } else {
                $currentIndex ++;
            }


            RotationStats::updateAll(
                [
                    'current_shows' => 0,
                    'current_clicks' => 0,
                    'current_index' => $currentIndex,
                    "shows{$currentIndex}" => (int) $thumbStat['current_shows'],
                    "clicks{$currentIndex}" => (int) $thumbStat['current_clicks'],
                ],
                [
                    'gallery_id' =>  $thumbStat['gallery_id'],
                    'category_id' =>  $thumbStat['category_id'],
                    'image_id' =>  $thumbStat['image_id'],
                ]
            );
        }
    }
}
