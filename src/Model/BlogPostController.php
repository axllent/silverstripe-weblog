<?php

namespace Axllent\Weblog\Model;

use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\HTML;
use SilverStripe\View\Requirements;

class BlogPostController extends \PageController
{
    /**
     * Add Open Graph headers to posts
     *
     * @config
     */
    private static $open_graph = true;

    /**
     * Index
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return HTTPResponse
     */
    public function index()
    {
        if ($this->config()->get('open_graph')) {
            $og = HTML::createTag(
                'meta',
                [
                    'property' => 'og:title',
                    'content'  => $this->dataRecord->Title,
                ]
            ) . "\n";
            $og .= HTML::createTag(
                'meta',
                [
                    'property' => 'og:url',
                    'content'  => $this->dataRecord->AbsoluteLink(),
                ]
            ) . "\n";
            $og .= HTML::createTag(
                'meta',
                [
                    'property' => 'og:site_name',
                    'content'  => SiteConfig::current_site_config()->Title,
                ]
            ) . "\n";
            if ($this->dataRecord->FeaturedImage()->exists()) {
                $og .= HTML::createTag(
                    'meta',
                    [
                        'property' => 'og:image',
                        'content'  => $this->dataRecord->FeaturedImage()->AbsoluteURL,
                    ]
                ) . "\n";
            }

            if ($this->dataRecord->Summary) {
                $content = DBText::create()->setValue($this->dataRecord->Summary);
            } else {
                $content = DBText::create()->setValue($this->dataRecord->Content);
            }
            $og .= HTML::createTag(
                'meta',
                [
                    'property' => 'og:description',
                    'content'  => strip_tags($content->Summary()),
                ]
            );

            Requirements::insertHeadTags(trim($og));
        }

        return $this->render();
    }
}
