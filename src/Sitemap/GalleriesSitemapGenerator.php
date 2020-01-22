<?php
namespace SK\GalleryModule\Sitemap;

use RS\Component\Core\Settings\SettingsInterface;
use samdark\sitemap\Sitemap;
use SK\SeoModule\Sitemap\SitemapHandlerInterface;
use SK\GalleryModule\Model\Gallery;
use Yii;

class GalleriesSitemapGenerator implements SitemapHandlerInterface
{
    private $filename = 'galleries.xml';
    private $urlManager;

    public function __construct(SettingsInterface $settings)
    {
        $siteUrl = $settings->get('site_url');

        $this->urlManager = Yii::$app->urlManager;
        $this->urlManager->setScriptUrl('/web/index.php');
        $this->urlManager->setHostInfo($siteUrl);
    }

    public function create(Sitemap $sitemap)
    {
        $models = \SK\GalleryModule\Model\Gallery::find()
            ->select(['gallery_id', 'slug', 'published_at'])
            ->untilNow()
            ->onlyActive()
            ->orderBy(['published_at' => SORT_DESC]);

        foreach ($models->batch(200) as $galleries) {
            foreach ($galleries as $gallery) {
                $sitemap->addItem($this->urlManager->createAbsoluteUrl(['/gallery/view/index', 'slug' => $gallery->slug]), strtotime($gallery->published_at), $sitemap::DAILY, 0.5);
            }
        }
    }

    public function getFilename(): string
    {
        return $this->filename;
    }
}
