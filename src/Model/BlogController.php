<?php

namespace Axllent\Weblog\Model;

use PageController;
use SilverStripe\Control\RSS\RSSFeed;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\PaginatedList;

class BlogController extends PageController
{
    private static $allowed_actions = array(
        'Archive',
        'rss'
    );

    private static $url_handlers = array(
        'archive//$Year!/$Month' => 'Archive'
    );

    public function index()
    {
        $this->blogPosts = $this->getBlogPosts();
        return $this->render();
    }

    /**
     * Archived posts either by year or year-month
     */
    public function Archive($request)
    {
        $year = $request->param('Year');
        $month = $request->param('Month');

        if (!$year || !is_numeric($year)) {
            return $this->httpError(404);
        }

        $publish_filter = $year . '-';

        if (is_numeric($month)) {
            $publish_filter .= $month . '-';
        }

        $this->blogPosts = $this->getBlogPosts()->filter(
            'PublishDate:StartsWith', $publish_filter
        );

        if (!$this->blogPosts->count()) {
            return $this->httpError(404);
        }

        return $this->render();
    }

    /**
     * Returns a list of paginated blog posts based on the BlogPost dataList.
     *
     * @return PaginatedList
     */
    public function PaginatedList()
    {
        $all_posts = $this->blogPosts ?: ArrayList::create();

        $posts = PaginatedList::create($all_posts);

        $posts->setPageLength($this->PostsPerPage);

        // Set current page
        $start = $this->request->getVar($posts->getPaginationGetVar());
        $posts->setPageStart($start);

        return $posts;
    }

    /**
     * Displays an RSS feed of blog posts.
     *
     * @return string
     */
    public function rss()
    {
        $this->blogPosts = $this->getBlogPosts();

        $rss = new RSSFeed($this->blogPosts, $this->Link(), $this->Title, $this->MetaDescription);

        $this->extend('updateRss', $rss);

        return $rss->outputToBrowser();
    }
}
