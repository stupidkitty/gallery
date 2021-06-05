<?php
namespace SK\GalleryModule\Import;

use SK\GalleryModule\Model\Gallery;
use SK\GalleryModule\Model\RotationStats;

class GalleryCreator
{
    public function createFromArray(array $data, array $extra = [])
    {
        $gallery = new Gallery();

        if (!empty($data['gallery_id'])) {
            $isExists = Gallery::find()
                ->where(['gallery_id' => $data['gallery_id']])
                ->exists();

            if ($isExists) { // пропустим вставку записи, т.к. галерея уже существует.
                return null;
            }

            $gallery->gallery_id = (int) $data['gallery_id'];
        }

        $currentTime = gmdate('Y-m-d H:i:s');
        $gallery->setAttributes($data);

        $title = (empty($data['title'])) ? 'default-' . microtime() : mb_substr($data['title'], 0, 255);
        $gallery->setTitle($title);

        $slug = empty($data['slug']) ? $gallery->title : $data['slug'];
        $slug = mb_substr($slug, 0, 245);
        $gallery->generateSlug($slug);

        // Время публикации поста, временный вариант.
        if (isset($data['published_at'])) {
            $gallery->published_at = $data['published_at'];
        } else {
            //$video->published_at = $this->getPublishedAt();
        }

        $gallery->updated_at = $currentTime;
        $gallery->created_at = $currentTime;

        if (!$gallery->save(true)) {
            return false;
        }

        if (isset($extra['categories']) && is_array($extra['categories'])) {
            foreach ($extra['categories'] as $category) {
                $gallery->addCategory($category);
            }
        }

        if (isset($extra['images']) && is_array($extra['images'])) {
            foreach ($extra['images'] as $i => $image) {
                $gallery->addImage($image);

                if (0 === $i) {
                    $gallery->setCoverImage($image);
                }
            }
        }

        foreach ($gallery->categories as $category) {
            foreach ($gallery->images as $key => $image) {
                RotationStats::addGallery($category, $gallery, $image);
            }
        }

        return $gallery;
    }
}
