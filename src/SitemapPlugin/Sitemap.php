<?php

namespace Selene\SitemapPlugin;

use Selene\StaticSite\PluginInterface;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\DomCrawler\Crawler;
use Thepixeldeveloper\Sitemap\Drivers\XmlWriterDriver;
use Thepixeldeveloper\Sitemap\Url;
use Thepixeldeveloper\Sitemap\Extensions\Image;
use Thepixeldeveloper\Sitemap\Urlset;

class Sitemap implements PluginInterface
{
    /**
     * This is the sitemap builder object.
     */
    protected $urlset;

    /**
     * Options array for the sitemap
     */
    protected $options = [
        'changefreq' => 'monthly',
        'include-images' => true,
        'include-links' => true,
        'domain'  => 'https://selenesoftware.us'
    ];

    protected $x = 0;

    public function setup(array $options)
    {
        $this->options = array_merge($this->options, $options);
    }

    public function start()
    {
        $this->urlset = new Urlset;
    }

    public function render(string $pageName, string $render = null): ?string
    {
        $url = $this->createUrl($pageName);
        $this->urlset->add($url);

        $dom = new Crawler($render);

        if ($this->options['include-images']) {
            $this->addImages($dom);
        }
        if ($this->options['include-links']) {
            // $this->addLinks($dom);
        }

        //Need to return rendered page content.  In our case, the rendered content we took in.
        return $render;
    }

    public function end()
    {
        $driver = new XmlWriterDriver();
        $this->urlset->accept($driver);

        $f = fopen(getcwd() . '/web/sitemap.xml', 'a');
        fwrite($f, $driver->output());
        fclose($f);
    }

    protected function createUrl(string $pageName): Url
    {
        if ($pageName[0] != '/') {
            $pageName = '/' . $pageName;
        }
        $url = new Url($this->options['domain'] . $pageName);

        if ($pageName === '/index') {
            $priority = "1.0";
        } else {
            $priority = "0.5";
        }
        $url->setLastMod(new \DateTime());
        $url->setChangeFreq($this->options['changefreq']);
        $url->setPriority($priority);

        return $url;
    }

    protected function addImages(Crawler $domCrawler): void
    {
        $dom = $domCrawler->filter('picture > img');

        if (!is_array($dom)) {
            $dom = [$dom];
        }
        foreach($dom as $d) {
            $src = $d->extract('src')[0];
            $alt = $d->extract('alt')[0];

            $url = $this->createUrl($src);

            $image = new Image($this->options['domain'] . $src);
            $image->setTitle($alt);

            $url->addExtension($image);
            $this->urlset->add($url);
            // unset($image);
        }
    }

    protected function addLinks(Crawler $domCrawler): void
    {

    }
}
