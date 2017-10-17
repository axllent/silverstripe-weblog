<?php

namespace Axllent\Weblog\Model;

use Axllent\Weblog\Forms\GridField\GridFieldConfig_BlogPost;
use Axllent\Weblog\Model\BlogPost;
use Page;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\Tab;
use SilverStripe\Lumberjack\Model\Lumberjack;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBInt;
use SilverStripe\ORM\FieldType\DBYear;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\View\ArrayData;

class Blog extends Page implements PermissionProvider
{
    private static $description = 'Adds a blog to your website.';

    private static $icon = 'axllent/silverstripe-weblog: icons/Blog.png';

    private static $table_name = 'Blog';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'PostsPerPage' => 'Int',
    ];

    private static $allowed_children = [
        BlogPost::class,
    ];

    private static $defaults = [
        'PostsPerPage'    => 10
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        /* Move the main tab to the end */
        $content_tab = $fields->findOrMakeTab('Root.Main');
        if ($content_tab && $content_fields = $content_tab->FieldList()) {
            $title = $content_tab->Title;
            $fields->removeByName('Main');

            $tab = $fields->findOrMakeTab('Root.Main');
            $tab->setTitle('Content');

            $fields->addFieldsToTab('Root.Main', $content_fields);
        }

        return $fields;
    }

    public function getSettingsFields()
    {
        $fields = parent::getSettingsFields();

        $fields->addFieldToTab('Root.Settings',
            NumericField::create('PostsPerPage', 'Blog posts per page'),
            'Visibility'
        );

        return $fields;
    }

    /**
     * This sets the title for our gridfield.
     *
     * @return string
     */
    public function getLumberjackTitle()
    {
        return 'Blog Posts';
    }

    /* Fix gridfield sorting by publish date */
    public function getLumberjackPagesForGridfield($included = array())
    {
        return BlogPost::get()->filter([
            'ParentID' => $this->owner->ID,
            'ClassName' => $included,
        ])->sort('"PublishDate" IS NULL DESC, "PublishDate" DESC');
    }

    /**
      * This overwrites lumberjacks default gridfield config.
      *
      * @return GridFieldConfig
      */
    public function getLumberjackGridFieldConfig()
    {
        return GridFieldConfig_BlogPost::create();
        $config->addComponent(new GridFieldSortableRows('Title'));
        return $config;
    }

    /**
     * Return visible blog posts.
     *
     * @return DataList of BlogPost objects
     */
    public function getBlogPosts()
    {
        $blog_posts = BlogPost::get()
            ->filter('ParentID', $this->ID)
            ->where(sprintf('"PublishDate" < \'%s\'', Convert::raw2sql(DBDatetime::now())));

        $this->extend('updateGetBlogPosts', $blog_posts);
        return $blog_posts;
    }

    /**
     * Return all years & months containing visible blog posts
     * @param null
     * @return ArrayData of Years and Months
     */
    public function getArchives()
    {
        $all_posts = $this->getBlogPosts();
        $years = [];
        $months = [];

        foreach ($all_posts as $post) {
            if (preg_match('/^(\d\d\d\d)-(\d\d)-/', $post->PublishDate, $match)) {
                if (empty($years[$match[1]])) {
                    $years[$match[1]] = 1;
                } else {
                    $years[$match[1]]++;
                }
                if (empty($months[$match[1] . '-' . $match[2]])) {
                    $months[$match[1] . '-' . $match[2]] = 1;
                } else {
                    $months[$match[1] . '-' . $match[2]]++;
                }
            }
        }
        $output = ArrayData::create([
            'Years' => ArrayList::create(),
            'Months' => ArrayList::create(),
        ]);
        $link_base = $this->Link() . 'archive/';

        foreach ($years as $year => $count) {
            $yr = DBYear::create();
            $yr->setValue($year);
            $cnt = DBInt::create();
            $cnt->setValue($count);
            $result = ArrayData::create([
                'Year' => $yr,
                'Count' => $cnt,
                'Link' => $link_base . $year . '/'
            ]);
            $output->Years->push($result);
        }

        foreach ($months as $month => $count) {
            $mth = DBDate::create();
            $mth->setValue($month . '-01');
            $cnt = DBInt::create();
            $cnt->setValue($count);
            $result = ArrayData::create([
                'Month' => $mth,
                'Count' => $cnt,
                'Link' => $link_base . str_replace('-', '/', $month) . '/'
            ]);
            $output->Months->push($result);
        }

        return $output;
    }

    /**
     * Update the PublishDate to now if the BlogPost would otherwise be published without a date.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->PostsPerPage) {
            $this->PostsPerPage = 10;
        }
        $this->extend('onBeforeWrite');
    }

    /**
     * Custom group for blog editing
     */
    public function providePermissions()
    {
        return array(
            'CMS_ACCESS_Weblog' => array(
                'name' => 'Weblog editor (create / edit posts)',
                'category' => _t('SilverStripe\\Security\\Permission.CONTENT_CATEGORY', 'CMS Access'),
                'help' => 'Overrules more specific access settings.',
                'sort' => 100
            )
        );
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
        }
        if (Permission::check('CMS_ACCESS_Weblog', 'any', $member)) {
            return true;
        };
        return parent::canView($member, $context);
    }
}
