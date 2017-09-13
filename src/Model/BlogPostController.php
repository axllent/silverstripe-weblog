<?php

namespace Axllent\Weblog\Model;

use PageController;
use SilverStripe\Control\RSS\RSSFeed;
use SilverStripe\View\Requirements;
use SilverStripe\View\HTML;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\FieldType\DBText;

// use SilverStripe\Core\Config\Config;
// use SilverStripe\ORM\ArrayList;
// use SilverStripe\ORM\FieldType\DBDatetime;
// use SilverStripe\ORM\PaginatedList;

class BlogPostController extends PageController
{

    /**
     * @config
     * Add Open Graph headers to posts
     */
    private static $open_graph = true;

    public function index()
    {
        if ($this->config()->get('open_graph')) {
            $og = HTML::createTag('meta', [
                'property' => 'og:title',
                'content' => $this->dataRecord->Title
            ]) . "\n";
            $og .= HTML::createTag('meta', [
                'property' => 'og:url',
                'content' => $this->dataRecord->AbsoluteLink()
            ]) . "\n";
            $og .= HTML::createTag('meta', [
                'property' => 'og:site_name',
                'content' => SiteConfig::current_site_config()->Title
            ]) . "\n";
            if ($this->dataRecord->FeaturedImage()->exists()) {
                $og .= HTML::createTag('meta', [
                    'property' => 'og:image',
                    'content' => $this->dataRecord->FeaturedImage()->AbsoluteURL
                ]) . "\n";
            }

            if ($this->dataRecord->Summary) {
                $content = DBText::create()->setValue($this->dataRecord->Summary);
            } else {
                $content = DBText::create()->setValue($this->dataRecord->Content);
            }
            $og .= HTML::createTag('meta', [
                'property' => 'og:description',
                'content' => strip_tags($content->Summary())
            ]);

            Requirements::insertHeadTags(trim($og));
        }

        RSSFeed::linkToFeed($this->Link() . 'rss', $this->Title);
        return $this->render();
    }
}
