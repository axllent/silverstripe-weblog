<?php

namespace Axllent\Weblog\Extensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Versioned\Versioned;

/**
 * Extension to show the published status of a DataObject that has
 * the Versioned extension on it
 */
class PublishedStatusExtension extends Extension
{
    public const STATUS_NOT_VERSIONED = 'Not versioned';
    public const STATUS_NOT_SAVED     = 'Not saved';
    public const STATUS_DRAFT         = 'Draft';
    public const STATUS_MODIFIED      = 'Modified';
    public const STATUS_PUBLISHED     = 'Published';

    /**
     * Shows the published status of a Versioned DataObject
     *
     * @return string
     */
    public function getPublishedStatus()
    {
        /** @var Versioned $obj */
        $obj = $this->owner;

        // get child statuses if applicable
        $relations     = Config::inst()->get($obj->ClassName, 'versioned_child_relations');
        $relations     = $relations ?: [];
        $childStatuses = [];
        foreach ($relations as $relation) {
            foreach ($obj->{$relation}() as $item) {
                $childStatuses[] = $item->getPublishedStatus();
            }
        }
        $childStatuses = array_flip(array_unique($childStatuses));

        // [Not versioned] - Object or Children don't have Versioned extension
        if (!$obj->hasExtension(Versioned::class)) {
            return self::STATUS_NOT_VERSIONED;
        }
        if (array_key_exists(self::STATUS_NOT_VERSIONED, $childStatuses)) {
            return 'Child ' . lcfirst(self::STATUS_NOT_VERSIONED);
        }

        // [Not saved] - Object or Child objects are not saved i.e. have an ID = 0
        if (0 == $obj->ID) {
            return self::STATUS_NOT_SAVED;
        }
        if (array_key_exists(self::STATUS_NOT_SAVED, $childStatuses)) {
            return 'Child' . lcfirst(self::STATUS_NOT_SAVED);
        }

        // [Draft] - Object is Draft
        if (null == Versioned::get_by_stage($obj->ClassName, 'Live')->byID($obj->ID)) {
            return self::STATUS_DRAFT;
        }

        // [Modified] - Object is modified or Child objects are modified or draft
        if (!$obj->latestPublished()
            || array_key_exists(self::STATUS_DRAFT, $childStatuses)
            || array_key_exists(self::STATUS_MODIFIED, $childStatuses)
        ) {
            return self::STATUS_MODIFIED;
        }

        // [Published] Object and Children are all published
        return self::STATUS_PUBLISHED;
    }

    /**
     * GridField published status
     *
     * @return void
     */
    public function getGridFieldPublishedStatus()
    {
        $status = $this->getPublishedStatus();

        $html = '<span class="gridfield-status status-' . Convert::raw2xml(strtolower($status)) . '">' .
            Convert::raw2xml($status) . '</span>';

        return DBHTMLText::create()->setValue($html);
    }
}
