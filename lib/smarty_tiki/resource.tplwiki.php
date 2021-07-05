<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * \brief Smarty plugin to use wiki page as a template resource parsing as little as with tpl on disk
 * -------------------------------------------------------------
 * File:     resource.tplwiki.php
 * Type:     resource
 * Name:     tplPage
 * Purpose:  Fetches a template from a wiki page but parsing as little as with tpl's on disk
 * -------------------------------------------------------------
 */
function smarty_resource_tplwiki_source(string $page, ?string &$tpl_source, Smarty_Tiki $smarty): bool
{

    $info = $smarty->checkWikiPageTemplatePerms($page, $tpl_source);

    if ($info) {
        $tpl_source = $info['data'];
        return true;
    } else {
        return false;
    }
}

function smarty_resource_tplwiki_timestamp($page, &$tpl_timestamp, $smarty)
{
    global $tikilib;

    $info = $tikilib->get_page_info($page);
    if (empty($info)) {
        return false;
    }
    if (preg_match('/\{([A-z-Z0-9_]+) */', $info['data']) || preg_match('/\{\{.+\}\}/', $info['data'])) { // there are some plugins - so it can be risky to cache the page
        $tpl_timestamp = $tikilib->now;
    } else {
        $tpl_timestamp = $info['lastModif'];
    }
    return true;
}

function smarty_resource_tplwiki_secure($tpl_name, $smarty)
{
    return true;
}

function smarty_resource_tplwiki_trusted($tpl_name, $smarty)
{
    return true;
}
