<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_MySql_TypeFactory implements Search_Type_Factory_Interface
{
    public function plaintext($value)
    {
        return new Search_Type_PlainText($value);
    }

    public function plainmediumtext($value)
    {
        return new Search_Type_PlainMediumText($value);
    }

    public function wikitext($value)
    {
        return new Search_Type_WikiText($value);
    }

    public function timestamp($value, $dateOnly = false)
    {
        if (is_numeric($value)) {
            // dates and times are stored in GMT
            if ($dateOnly) {
                return new Search_Type_Timestamp(gmdate('Y-m-d', $value), true);
            } else {
                return new Search_Type_Timestamp(gmdate('Y-m-d H:i:s', $value));
            }
        } else {
            // if mysql sql_mode is set to NO_ZERO_IN_DATE or NO_ZERO_DATE then'0000-00-00 00:00:00' produces errors
            return new Search_Type_Timestamp(null, $dateOnly);
        }
    }

    public function identifier($value)
    {
        return new Search_Type_Whole($value);
    }

    public function numeric($value)
    {
        return new Search_Type_Numeric($value);
    }

    public function multivalue($values)
    {
        return new Search_Type_MultivalueText((array) $values);
    }

    /* Not supported in MySQL indexes - use elasticsearch*/
    public function object($values)
    {
        return null;
    }
    /* Not supported in MySQL indexes - use elasticsearch */
    public function nested($values)
    {
        return null;
    }

    /* Not supported in MySQL indexes - use elasticsearch */
    public function geopoint($values)
    {
        return null;
    }

    public function sortable($value)
    {
        return new Search_Type_PlainShortText($value);
    }

    /* Not supported in MySQL indexes but treating as sortable */
    public function simpletext($value)
    {
        return new Search_Type_PlainShortText($value);
    }

    /* Not fully supported in MySQL indexes - elasticsearch recommended */
    public function json($value)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        return new Search_Type_PlainText($value);
    }
}
