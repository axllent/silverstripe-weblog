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
                'content' => $this->Title
            ]) . "\n";
            $og .= HTML::createTag('meta', [
                'property' => 'og:url',
                'content' => $this->AbsoluteLink()
            ]) . "\n";
            $og .= HTML::createTag('meta', [
                'property' => 'og:site_name',
                'content' => SiteConfig::current_site_config()->Title
            ]) . "\n";
            if ($this->FeaturedImage()->exists()) {
                $og .= HTML::createTag('meta', [
                    'property' => 'og:image',
                    'content' => $this->FeaturedImage()->AbsoluteURL
                ]) . "\n";
            }

            $content = DBText::create()->setValue($this->PostSummary());

            $og .= HTML::createTag('meta', [
                'property' => 'og:description',
                'content' => strip_tags($content->Summary())
            ]) . "\n";

            Requirements::insertHeadTags(trim($og));
        }

        //         <meta property="og:title" content="Store your gear in one place with the Carry Wash &amp; Store Bag"/>
        // 		<meta property="og:type" content="article"/>
        // 		<meta property="og:url" content="https://www.railblaza.com/store-your-gear-in-one-place-with-the-carry-wash-store-bag/"/>
        //
        // 		<meta property="og:description" content="C.W.S Bag (Carry Wash Store)
        // Keeping gear together and losing small parts on a boat, kayak or even in the shed has its challenges. Washing it down one piece at a time takes ages. RAILBLAZA have the answer with the new CWS Bag. This mesh bag is designed to not only keep your"/>
        //
        // 									<meta property="og:image" content="https://www.railblaza.com/wp-content/uploads/2015/10/Carry-Wash-Stow-bag-121.jpg"/>

        RSSFeed::linkToFeed($this->Link() . 'rss', $this->Title);
        return $this->render();
    }
}
