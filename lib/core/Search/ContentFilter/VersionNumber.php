<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * This is to implement pref 'unified_tokenize_version_numbers' to Tokenize version number strings so that major versions are found when sub-versions are mentioned. For example, searching for 2.7 would return documents containing 2.7.4, but not 1.2.7.
 */
class Search_ContentFilter_VersionNumber implements Laminas\Filter\FilterInterface
{
    public function filter($value)
    {
        return preg_replace_callback('/[0-9]+(\.[0-9]+)+/', [$this, 'augmentVersionTokens'], $value);
    }

    public function augmentVersionTokens($version)
    {
        $version = $version[0];
        $out = $version;

        $pos = -1;
        while (false !== $pos = strpos($version, '.', $pos + 1)) {
            $out .= ' ' . substr($version, 0, $pos);
        }

        return $out;
    }
}
