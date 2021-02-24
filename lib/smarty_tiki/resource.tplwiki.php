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
 * \brief Smarty plugin to use wiki page as a template resource parsing as little as with tpl on disk
 * -------------------------------------------------------------
 * File:     resource.tplwiki.php
 * Type:     resource
 * Name:     tplPage
 * Purpose:  Fetches a template from a wiki page but parsing as little as with tpl's on disk
 * -------------------------------------------------------------
 */
function smarty_resource_tplwiki_source($page, &$tpl_source, $smarty)
{
	global $tikilib, $user;

	$perms = Perms::get([ 'type' => 'wiki page', 'object' => $page ]);
	if (! $perms->use_as_template) {
		$tpl_source = tra('Permission denied: the specified wiki page cannot be used as Smarty template resource') . '<br />';
		// TODO: do not cache ! and return the message only once should be enough...
		return true;
	}

	// check perms for non-admin editors but only show to admins
	if ($perms->admin_wiki) {
		$loaded = $perms->getResolver()->dump();
		$nonAdminEditorGroups = [];
		foreach ($loaded['perms']['edit'] as $editorGroup) {
			if ($editorGroup !== 'Admins' && ! in_array($editorGroup, $loaded['perms']['admin_wiki'])) {
				$nonAdminEditorGroups[] = $editorGroup;
			}
		}
		if ($nonAdminEditorGroups) {
			$groupString = implode(', ', $nonAdminEditorGroups);
			$smarty->loadPlugin('smarty_modifier_sefurl');
			$pageLink = '<a href="' . smarty_modifier_sefurl($page) . '" class="alert-link">' . $page . '</a>';
			if (count($nonAdminEditorGroups) > 1) {
				$message = 'The %0 groups can edit this template page %1 but are not wiki administrators';
				$groupString = 	substr_replace($groupString, tr(' and'), strrpos($groupString, ','), 1);
			} else {
				$message = 'The %0 group can edit this template page %1 but is not a wiki administrator';
			}
			Feedback::warning(tr($message, $groupString, $pageLink));
		}
	}

	$info = $tikilib->get_page_info($page);
	if (empty($info)) {
		return false;
	}
	$tpl_source = $info['data'];
	return true;
}

function smarty_resource_tplwiki_timestamp($page, &$tpl_timestamp, $smarty)
{
	global $tikilib, $user;
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
