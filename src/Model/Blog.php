<?php

namespace Axllent\Weblog\Model;

use Axllent\Weblog\Forms\GridField\GridFieldConfig_BlogPost;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\Tab;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBInt;
use SilverStripe\ORM\FieldType\DBYear;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\View\ArrayData;

class Blog extends \Page implements PermissionProvider
{
    /**
     * Page description
     *
     * @var string
     *
     * @config
     */
    private static $description = 'Adds a blog to your website.';

    /**
     * Page icon class
     *
     * @var string
     *
     * @config
     */
    private static $icon = 'axllent/silverstripe-weblog: icons/Blog.png';

    /**
     * Table name
     *
     * @var string
     */
    private static $table_name = 'Blog';

    /**
     * Database field definitions
     *
     * @var array
     *
     * @config
     */
    private static $db = [
        'PostsPerPage' => 'Int',
    ];

    /**
     * Allowed children
     *
     * @var array
     *
     * @config
     */
    private static $allowed_children = [
        BlogPost::class,
    ];

    /**
     * DataObject defaults
     *
     * @var array
     *
     * @config
     */
    private static $defaults = [
        'PostsPerPage' => 10,
    ];

    /**
     * Data administration interface in Silverstripe
     *
     * @return FieldList Returns a TabSet for usage within the CMS
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Move the main tab to the end
        $content_tab = $fields->findOrMakeTab('Root.Main');
        if ($content_tab && $content_fields = $content_tab->FieldList()) {
            $title = $content_tab->Title;
            $fields->removeByName('Main');

            $tab = $fields->findOrMakeTab('Root.Main');
            $tab->setTitle('Content');

            $fields->addFieldsToTab('Root.Main', $content_fields);
        }

        $this->extend('updateBlogCMSFields', $fields);

        return $fields;
    }

    /**
     * Get settings fields
     *
     * @return FieldList
     */
    public function getSettingsFields()
    {
        $fields = parent::getSettingsFields();

        $fields->addFieldToTab(
            'Root.Settings',
            NumericField::create('PostsPerPage', 'Blog posts per page'),
            'Visibility'
        );

        return $fields;
    }

    /**
     * This sets the title for our GridField.
     *
     * @return string
     */
    public function getLumberjackTitle()
    {
        return 'Blog posts';
    }

    // Fix GridField sorting by publish date
    public function getLumberjackPagesForGridField($included = [])
    {
        $filtered = BlogPost::get()->filter(
            [
                'ParentID'  => $this->owner->ID,
                'ClassName' => $included,
            ]
        );

        $sort = '"PublishDate" IS NULL DESC, "PublishDate" DESC';

        // Silverstripe 5 performs more strict validation of columns
        // A new API method is provided when using raw SQL (i.e. IS NULL)
        return method_exists($filtered, 'orderBy') ?
            $filtered->orderBy($sort) :
            $filtered->sort($sort);
    }

    /**
     * This overwrites lumberjacks default GridField config.
     *
     * @return GridFieldConfig
     */
    public function getLumberjackGridFieldConfig()
    {
        return GridFieldConfig_BlogPost::create();
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
     *
     * @param null
     *
     * @return ArrayData of Years and Months
     */
    public function getArchives()
    {
        $all_posts = $this->getBlogPosts();
        $years     = [];
        $months    = [];

        foreach ($all_posts as $post) {
            if (preg_match('/^(\d\d\d\d)-(\d\d)-/', $post->PublishDate, $match)) {
                if (empty($years[$match[1]])) {
                    $years[$match[1]] = 1;
                } else {
                    ++$years[$match[1]];
                }
                if (empty($months[$match[1] . '-' . $match[2]])) {
                    $months[$match[1] . '-' . $match[2]] = 1;
                } else {
                    ++$months[$match[1] . '-' . $match[2]];
                }
            }
        }
        $output = ArrayData::create(
            [
                'Years'  => ArrayList::create(),
                'Months' => ArrayList::create(),
            ]
        );

        $link_base = $this->Link('archive');

        foreach ($years as $year => $count) {
            $yr = DBYear::create();
            $yr->setValue($year);
            $cnt = DBInt::create();
            $cnt->setValue($count);
            $result = ArrayData::create(
                [
                    'Year'  => $yr,
                    'Count' => $cnt,
                    'Link'  => rtrim($link_base, '/') . '/' . $year . '/',
                ]
            );
            $output->Years->push($result);
        }

        foreach ($months as $month => $count) {
            $mth = DBDate::create();
            $mth->setValue($month . '-01');
            $cnt = DBInt::create();
            $cnt->setValue($count);
            $result = ArrayData::create(
                [
                    'Month' => $mth,
                    'Count' => $cnt,
                    'Link'  => rtrim($link_base, '/') . '/' . str_replace('-', '/', $month) . '/',
                ]
            );
            $output->Months->push($result);
        }

        return $output;
    }

    /**
     * Event handler called before writing to the database
     *
     * @return void
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
     * Provide permissions
     *
     * @return void
     */
    public function providePermissions()
    {
        return [
            'CMS_ACCESS_Weblog' => [
                'name'     => 'Weblog editor (create / edit posts)',
                'category' => _t('SilverStripe\\Security\\Permission.CONTENT_CATEGORY', 'CMS Access'),
                'help'     => 'Overrules more specific access settings.',
                'sort'     => 100,
            ],
        ];
    }

    /**
     * Can add children
     *
     * @param Member $member Member
     *
     * @return bool
     */
    public function canAddChildren($member = null)
    {
        $extended = $this->extendedCan('canAddChildren', $member);
        if (null !== $extended) {
            return $extended;
        }
        if (Permission::check('CMS_ACCESS_Weblog', 'any', $member)) {
            return true;
        }

        return parent::canAddChildren($member);
    }
}
