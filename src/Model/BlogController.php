<?php

namespace Axllent\Weblog\Model;

use SilverStripe\Control\RSS\RSSFeed;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\PaginatedList;

class BlogController extends \PageController
{
    /**
     * Set Blog Posts
     *
     * @var DataList
     */
    public $blogPosts = DataList::class;

    /**
     * Allowed actions
     *
     * @var array
     *
     * @config
     */
    private static $allowed_actions = [
        'viewArchive',
        'rss',
    ];

    /**
     * URL handlers for controller
     *
     * @var array
     */
    private static $url_handlers = [
        'archive//$Year!/$Month' => 'viewArchive',
    ];

    /**
     * Index
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return HTTPResponse
     */
    public function index($request)
    {
        $this->blogPosts = $this->getBlogPosts();
        RSSFeed::linkToFeed($this->Link('rss'), $this->Title);

        return $this->render();
    }

    /**
     * Display archived posts either by year or year-month
     *
     * @param mixed $request
     */
    public function viewArchive($request)
    {
        $year  = $request->param('Year');
        $month = $request->param('Month');

        $this->blogPosts = $this->getArchivesByDate();

        if (!$this->blogPosts->count()) {
            return $this->httpError(404);
        }

        $title = $year;

        if ($month) {
            $title = $month . '/' . $year;
        }

        $orig_title = $this->dataRecord->Title;

        $this->Title = 'Archived posts for "' . $title . '" | ' . $orig_title;

        $this->ArchivePeriod = $title;

        return $this->render();
    }

    /**
     * Return archived posts for year-[month-]
     *
     * @param null
     *
     * @return ArrayList
     */
    public function getArchivesByDate()
    {
        $year  = $this->request->param('Year');
        $month = $this->request->param('Month');

        if (!$year || !is_numeric($year)) {
            return ArrayList::create();
        }

        $publish_filter = $year . '-';

        if (is_numeric($month)) {
            $publish_filter .= $month . '-';
        }

        return $this->getBlogPosts()->filter(
            'PublishDate:StartsWith',
            $publish_filter
        );
    }

    /**
     * Returns a list of paginated blog posts based on the BlogPost dataList.
     *
     * @return PaginatedList
     */
    public function paginatedList()
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
        $this->blogPosts = $this->getBlogPosts()->limit($this->PostsPerPage);

        $rss = new RSSFeed($this->blogPosts, $this->Link(), $this->Title, $this->MetaDescription);

        $this->extend('updateRss', $rss);

        return $rss->outputToBrowser();
    }
}
