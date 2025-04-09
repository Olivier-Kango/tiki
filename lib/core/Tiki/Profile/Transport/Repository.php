<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Profile_Transport_Repository implements Tiki_Profile_Transport_Interface
{
    private $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function getPageContent($pageName)
    {
        // Get page content directly from database
        $info = TikiLib::lib('tiki')->get_page_info($pageName);

        if ($info && isset($info['data'])) {
            $content = $info['data'];
            $content = str_replace("\r", '', $content);
            return $content;
        }

        return null;
    }

    public function getPageParsed($pageName)
    {
        $pageUrl = dirname($this->url) . '/tiki-index_raw.php?'
            . http_build_query([ 'page' => $pageName ]);

        $content = TikiLib::lib('tiki')->httprequest($pageUrl);
        // index_raw replaces index.php with itself, so undo that here
        $content = str_replace('tiki-index_raw.php', 'tiki-index.php', $content);

        return $content;
    }

    public function getProfilePath()
    {
        return $this->url;
    }
}
