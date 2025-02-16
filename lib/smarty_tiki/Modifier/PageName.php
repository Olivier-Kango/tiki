<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

class PageName
{
    public function handle($source)
    {
        global $prefs;
        if (! empty($prefs['wiki_pagename_strip']) || $prefs['namespace_indicator_in_page_title'] == 'y') {
            if (! empty($prefs['wiki_pagename_strip'])) {
                $wiki_strip = '~' . preg_quote($prefs['wiki_pagename_strip']) . '.*$~';
                $source = preg_replace($wiki_strip, '', $source);
            }
            if ($prefs['namespace_indicator_in_page_title'] == 'y') {
                $wiki_namespace = '~.* / ~';
                $source = preg_replace($wiki_namespace, '', $source);
            }
            return $source;
        } else {
            return $source;
        }
    }
}
