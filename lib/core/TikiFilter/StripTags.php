<?php

use Laminas\Filter\StripTags;

class TikiFilter_StripTags extends StripTags
{
    public function filter($value)
    {
        if (! isset($value)) {
            return '';
        }
        return parent::filter($value);
    }
}
