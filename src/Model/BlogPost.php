<?php

namespace Axllent\Weblog\Model;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\TextAreaField;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Permission;

class BlogPost extends \Page
{
    /**
     * Table name
     *
     * @var string
     */
    private static $table_name = 'BlogPost';

    /**
     * Can be root
     *
     * @var bool
     *
     * @config
     */
    private static $can_be_root = false;

    /**
     * The default parent
     *
     * @var string
     *
     * @config
     */
    private static $default_parent = 'Blog';

    /**
     * Page icon
     *
     * @var string
     *
     * @config
     */
    private static $cms_icon = 'axllent/silverstripe-weblog: icons/BlogPost.png';

    /**
     * Default featured image folder
     *
     * @var string
     *
     * @config
     */
    private static $featured_image_folder = 'Blog';

    /**
     * Show in SiteTree
     *
     * @var bool
     *
     * @config
     */
    private static $show_in_sitetree = false;

    /**
     * Database field definitions
     *
     * @var array
     *
     * @config
     */
    private static $db = [
        'PublishDate' => 'Datetime',
        'Summary'     => 'Text',
    ];

    /**
     * Use a casting object for a field
     *
     * @var array
     *
     * @config
     */
    private static $casting = [
        'Date' => 'DBDatetime',
    ];

    /**
     * Provides a default list of fields to be used by a 'summary'
     * view of this object
     *
     * @var string
     *
     * @config
     */
    private static $summary_fields = [
        'Title'                       => 'Title',
        'getGridFieldPublishedStatus' => 'Status',
    ];

    /**
     * One-to-zero relationship definitions
     *
     * @var array
     *
     * @config
     */
    private static $has_one = [
        'FeaturedImage' => Image::class,
    ];

    /**
     * List of relationships on this object that are "owned" by this object
     *
     * @var string
     *
     * @config
     */
    private static $owns = [
        'FeaturedImage',
    ];

    /**
     * Defaults
     *
     * @var array
     *
     * @config
     */
    private static $defaults = [
        'ShowInMenus' => false,
    ];

    /**
     * The default sorting lists BlogPosts with an empty PublishDate at the top.
     */
    /**
     * The default sort
     *
     * @var string
     *
     * @config
     */
    private static $default_sort = '"PublishDate" IS NULL DESC, "PublishDate" DESC';

    /**
     * Allowed children
     *
     * @var array
     *
     * @config
     */
    private static $allowed_children = [];

    /**
     * Data administration interface in Silverstripe
     *
     * @return FieldList Returns a TabSet for usage within the CMS
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('MenuTitle');

        // Set the Title to "Post Title"
        if ($title = $fields->dataFieldByName('Title')) {
            $title->setTitle('Post title');
        }

        $featured_image = UploadField::create(
            'FeaturedImage',
            'Featured image'
        );
        $featured_image->setFolderName($this->config()->get('featured_image_folder'));
        $featured_image->getValidator()->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif']);

        $fields->addFieldToTab(
            'Root.Main',
            $featured_image,
            'Content'
        );

        $url_segment = $fields->dataFieldByName('URLSegment');

        if ($url_segment) {
            $fields->addFieldToTab('Root.PostOptions', $url_segment);
        }

        $fields->addFieldToTab(
            'Root.PostOptions',
            $publish_date = DatetimeField::create('PublishDate')
        );

        $publish_date->setDescription(
            'Will be set to "now" if published without a value.'
        );

        $fields->addFieldToTab(
            'Root.PostOptions',
            TextAreaField::create('Summary', 'Post summary')
                ->setRightTitle('If used, this will be shown in the blog post overview instead of an excerpt')
        );

        $this->extend('updateBlogPostCMSFields', $fields);

        return $fields;
    }

    /**
     * Display the publish date in rss feeds.
     *
     * @return null|string
     */
    public function getDate()
    {
        return !empty($this->PublishDate) ? $this->PublishDate : null;
    }

    public function previousBlogPost()
    {
        $all_posts = $this->Parent()->getBlogPosts();

        return $all_posts->filter('PublishDate:LessThan', $this->PublishDate)
            ->exclude('ID', $this->ID)
            ->sort(['PublishDate' => 'DESC', 'ID' => 'ASC'])
            ->limit(1)
            ->first();
    }

    public function nextBlogPost()
    {
        $all_posts = $this->Parent()->getBlogPosts();

        return $all_posts->filter('PublishDate:GreaterThan', $this->PublishDate)
            ->exclude('ID', $this->ID)
            ->sort(['PublishDate' => 'ASC', 'ID' => 'ASC'])
            ->limit(1)
            ->first();
    }

    /**
     * Ensure the Parent is a Blog, if not move it to the first blog
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $parent = $this->Parent();
        if ($parent->exists() && !$parent instanceof Blog) {
            $first_blog = Blog::get()->first();
            if ($first_blog) {
                $this->ParentID = $first_blog->ID;
            }
        }

        $this->extend('onBeforeWrite');
    }

    /**
     * Update the PublishDate to now if the BlogPost would otherwise be published without a date.
     *
     * @param DataObject $original Original record
     *
     * @return void
     */
    public function onBeforePublish(&$original)
    {
        /**
         * @var DBDatetime $publishDate
         */
        $publishDate = $this->dbObject('PublishDate');
        if (!$publishDate->getValue()) {
            $this->PublishDate = DBDatetime::now()->getValue();
        }

        $this->Summary = trim((string) $this->Summary);

        $this->extend('onBeforePublish', $original);
    }

    public function canEdit($member = null, $context = [])
    {
        $extended = $this->extendedCan('canEdit', $member);
        if (null !== $extended) {
            return $extended;
        }
        if (Permission::check('CMS_ACCESS_Weblog', 'any', $member)) {
            return true;
        }

        return parent::canEdit($member, $context);
    }

    public function canView($member = null, $context = [])
    {
        $extended = $this->extendedCan('canView', $member);
        if (null !== $extended) {
            return $extended;
        }
        if (strtotime((string) $this->PublishDate) < time()) {
            return true;
        }

        return Permission::check('CMS_ACCESS_Weblog', 'any', $member);
    }

    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan('canCreate', $member);
        if (null !== $extended) {
            return $extended;
        }
        $parent               = isset($context['Parent']) ? $context['Parent'] : null;
        $strictParentInstance = ($parent && $parent instanceof Blog);
        if ($strictParentInstance) {
            return Permission::check('CMS_ACCESS_Weblog', 'any', $member);
        }

        return false;
    }

    public function canDelete($member = null, $context = [])
    {
        $extended = $this->extendedCan('canDelete', $member);
        if (null !== $extended) {
            return $extended;
        }
        if (Permission::check('CMS_ACCESS_Weblog', 'any', $member)) {
            return true;
        }

        return parent::canDelete($member, $context);
    }

    public function canPublish($member = null, $context = [])
    {
        $extended = $this->extendedCan('canPublish', $member);
        if (null !== $extended) {
            return $extended;
        }
        if (Permission::check('CMS_ACCESS_Weblog', 'any', $member)) {
            return true;
        }

        return parent::canPublish($member, $context);
    }
}
