<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Manticore_Prefilter
{
    /**
     * Returns Manticore prefilter logic to possibly insert in a closure
     * @param $fields
     * @param $entry
     * @return array
     */
    public function get($fields, $entry)
    {
        return array_filter(
            $fields,
            function ($field) use ($entry) {
                if (! isset($entry[$field])) {
                    return true;
                }
                // MVA is an array of ints - crc32 values should be re-fetched from db when displaying
                if (is_array($entry[$field])) {
                    $ints = array_filter($entry[$field], function($elem) {
                        return is_numeric($elem) && (string)intval($elem) == $elem;
                    });
                    if (count($ints) == count($entry[$field])) {
                        return true;
                    }
                }
                return false;
            }
        );
    }
}
