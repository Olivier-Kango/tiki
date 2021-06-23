<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}
/**
 * \brief Smarty plugin to use wiki page as a template resource
 * -------------------------------------------------------------
 * File:     resource.wiki.php
 * Type:     resource
 * Name:     wiki
 * Purpose:  Fetches a template from a wiki page
 * -------------------------------------------------------------
 */
function smarty_resource_wiki_source(string $page, ?string &$tpl_source, Smarty_Tiki $smarty)
{
	$info = $smarty->checkWikiPageTemplatePerms($page, $tpl_source);

	if ($info) {
		$tpl_source = TikiLib::lib('parser')->parse_data($info['data'], ['is_html' => $info['is_html'], 'print' => 'y', 'inside_pretty' => true]);
		return true;
	} else {
		return false;
	}
}

function smarty_resource_wiki_timestamp($page, &$tpl_timestamp, $smarty)
{
	global $tikilib, $user;
	$info = $tikilib->get_page_info($page);
	if (empty($info)) {
		return false;
	}
	if (preg_match('/\{([A-z-Z0-9_]+) */', $info['data']) || preg_match('/\{\{.+\}\}/', $info['data'])) { // there are some plugins - so it can be risky to cache the page
		$tpl_timestamp = $tikilib->now + 100; // future needed in case consecutive run of template;
	} else {
		$tpl_timestamp = $info['lastModif'];
	}
	return true;
}

function smarty_resource_wiki_secure($tpl_name, $smarty)
{
	return true;
}

function smarty_resource_wiki_trusted($tpl_name, $smarty)
{
	return true;
}
