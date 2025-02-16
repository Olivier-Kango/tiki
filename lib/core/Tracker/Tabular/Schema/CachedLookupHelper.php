<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular\Schema;

use TikiDb;

/**
 * Helper class to create an in-memory cache to avoid excessive database queries while
 * importing. The class will initially attempt to load a reasonable amount of data. If
 * the source contains more values than could reasonably be loaded, additional values
 * will be looked up one at a time before being cached.
 */
class CachedLookupHelper
{
    private $baseCount;
    private $cache = [];
    private $init;
    private $lookup;
    private $enableLookup = false;

    public function __construct($baseCount = 100)
    {
        $this->baseCount = $baseCount;
    }

    public function setInit(callable $fn)
    {
        $this->init = $fn;
    }

    public function setLookup(callable $fn)
    {
        $this->lookup = $fn;
        $this->enableLookup = true;
    }

    public function get($value)
    {
        if ($this->init) {
            // Enable lookup on missing values only if not all values have been initially
            // loaded after attempting to load a fixed amount.
            $this->cache = call_user_func($this->init, $this->baseCount);
            $this->enableLookup = $this->enableLookup && count($this->cache) >= $this->baseCount;
            if (! $this->enableLookup) {
                // if there are duplicates on the field being looked fetchMap removes them, so double check to see it there might be more
                if (count(call_user_func($this->init, $this->baseCount * 2))) {
                    $this->enableLookup = true;
                }
            }
            $this->init = null;
        }

        if (isset($this->cache[$value])) {
            return $this->cache[$value];
        }

        if ($this->enableLookup) {
            return $this->cache[$value] = call_user_func($this->lookup, $value);
        }
    }

    public static function fieldLookup($fieldId)
    {
        $table = TikiDb::get()->table('tiki_tracker_item_fields');

        $cache = new self();
        $cache->setInit(function ($count) use ($table, $fieldId) {
            return $table->fetchMap('itemId', 'value', [
                'fieldId' => $fieldId,
            ], $count, 0);
        });
        $cache->setLookup(function ($value) use ($table, $fieldId) {
            return $table->fetchOne('value', [
                'fieldId' => $fieldId,
                'itemId' => $value,
            ]);
        });

        return $cache;
    }

    public static function fieldInvert($fieldId)
    {
        $table = TikiDb::get()->table('tiki_tracker_item_fields');

        $cache = new self();
        $cache->setInit(function ($count) use ($table, $fieldId) {
            return $table->fetchMap('value', 'itemId', [
                'fieldId' => $fieldId,
                // getting items with empty values is pointless for a map of `value` => `itemId`
                // as that is returned as a single item so LENGTH() > 0 filters out empty strings and NULL
                'value' => $table->expr('LENGTH($$) > 0')
            ], $count, 0);
        });
        $cache->setLookup(function ($value) use ($table, $fieldId) {
            return $table->fetchOne('itemId', [
                'fieldId' => $fieldId,
                'value' => $value,
            ]);
        });

        return $cache;
    }
}
