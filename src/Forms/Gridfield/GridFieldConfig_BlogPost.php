<?php

namespace Axllent\Weblog\Forms\GridField;

use SilverStripe\Lumberjack\Forms\GridFieldConfig_Lumberjack;
use SilverStripe\Lumberjack\Forms\GridFieldSiteTreeState;

/**
 * GridField config necessary for managing a SiteTree object.
 */
class GridFieldConfig_BlogPost extends GridFieldConfig_Lumberjack
{
    /**
     * Lumberjack items per page
     *
     * @param int $itemsPerPage Items per page
     *
     * @return void
     */
    public function __construct($itemsPerPage = null)
    {
        parent::__construct($itemsPerPage);

        $this->removeComponentsByType(GridFieldSiteTreeState::class);
        $this->addComponent(new GridFieldBlogPostState());
    }
}
