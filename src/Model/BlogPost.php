<?php

namespace Axllent\Weblog\Model;

use Page;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\TextAreaField;

class BlogPost extends Page
{
    private static $table_name = 'BlogPost';

    private static $can_be_root = false;

    private static $default_parent = 'Blog';

    private static $icon = 'silverstripe-weblog/icons/BlogPost.png';

    private static $featured_image_folder = 'Blog';

    private static $show_in_sitetree = false;

    private static $db = [
        'PublishDate' => 'Datetime',
        'Summary'     => 'Text'
    ];

    /**
     * @var array
     */
    private static $casting = [
        'Date' => 'DBDatetime',
        'PostSummary' => 'HTMLFragment'
    ];

    private static $summary_fields = array(
        'Title'
    );

    private static $has_one = array(
        'FeaturedImage' => Image::class
    );

    private static $owns = array(
        'FeaturedImage',
    );

    private static $defaults = array(
        'ShowInMenus'     => false,
    );

    /**
     * The default sorting lists BlogPosts with an empty PublishDate at the top.
     */
    private static $default_sort = '"PublishDate" IS NULL DESC, "PublishDate" DESC';

    /**
     * @var array
     */
    private static $allowed_children = [];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('MenuTitle');

        $featured_image = UploadField::create(
            'FeaturedImage',
            'Featured Image'
        );
        $featured_image->setFolderName($this->config()->get('featured_image_folder'));
        $featured_image->getValidator()->setAllowedExtensions(array('jpg', 'jpeg', 'png', 'gif'));

        $fields->addFieldToTab(
            'Root.Main',
            $featured_image,
            'Content'
        );

        $url_segment = $fields->dataFieldByName('URLSegment');

        $fields->addFieldsToTab('Root.PostOptions', [
            $url_segment,
            $publish_date = DatetimeField::create('PublishDate')
        ]);

        $publish_date->setDescription(
            'Will be set to "now" if published without a value.'
        );

        $fields->addFieldToTab('Root.PostOptions',
            TextAreaField::create('Summary', 'Post Summary')
                ->setRightTitle('If used, this will be shown in the blog post overview instead of an excerpt')
        );

        return $fields;
    }


    /**
     * An extendible post summary for use in overview and Open Data
     * @return html
     */
    public function PostSummary()
    {
        $extended = $this->extend('updatePostSummary');
        if ($extended) {
            return $extended;
        }

        if ($this->Summary) {
            $summary = $this->Summary;
        } else {
            $summary = $this->Content;
        }
        return $summary;
        // return $this-
    }


    /**
     * Display the publish date in rss feeds.
     *
     * @return string|null
     */
    public function getDate()
    {
        return !empty($this->PublishDate) ? $this->PublishDate : null;
    }

    public function PreviousBlogPost()
    {
        $all_posts = $this->Parent()->getBlogPosts();
        return $all_posts->filter('PublishDate:LessThan', $this->PublishDate)
            ->exclude("ID", $this->ID)
            ->sort(array('PublishDate' => 'DESC', 'ID' => 'ASC'))
            ->limit(1)
            ->first();
    }

    public function NextBlogPost()
    {
        $all_posts = $this->Parent()->getBlogPosts();
        return $all_posts->filter('PublishDate:GreaterThan', $this->PublishDate)
            ->exclude("ID", $this->ID)
            ->sort(array('PublishDate' => 'ASC', 'ID' => 'ASC'))
            ->limit(1)
            ->first();
    }

    /**
     * Update the PublishDate to now if the BlogPost would otherwise be published without a date.
     */
    public function onBeforePublish()
    {
        /**
         * @var DBDatetime $publishDate
         */
        $publishDate = $this->dbObject('PublishDate');
        if (!$publishDate->getValue()) {
            $this->PublishDate = DBDatetime::now()->getValue();
            $this->write();
        }

        $this->Summary = trim($this->Summary);

        $this->extend('onBeforePublish');
    }

    public function canEdit($member = null, $context = [])
    {
        $extended = $this->extendedCan('canEdit', $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::check('CMS_ACCESS_Weblog', 'any', $member)) {
            return true;
        };
        return parent::canEdit($member, $context);
    }

    public function canView($member = null, $context = [])
    {
        $extended = $this->extendedCan('canView', $member);
        if ($extended !== null) {
            return $extended;
        } elseif (strtotime($this->PublishDate) < time()) {
            return true;
        } else {
            return Permission::check('CMS_ACCESS_Weblog', 'any', $member);
        }
    }

    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan('canCreate', $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::check('CMS_ACCESS_Weblog', 'any', $member)) {
            return true;
        };
        return parent::canCreate($member, $context);
    }

    public function canDelete($member = null, $context = [])
    {
        $extended = $this->extendedCan('canDelete', $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::check('CMS_ACCESS_Weblog', 'any', $member)) {
            return true;
        };
        return parent::canDelete($member, $context);
    }

    public function canPublish($member = null, $context = [])
    {
        $extended = $this->extendedCan('canPublish', $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::check('CMS_ACCESS_Weblog', 'any', $member)) {
            return true;
        };
        return parent::canPublish($member, $context);
    }
}
