<?php

// $Header: /cvsroot/tikiwiki/tiki/tiki-article_types.php,v 1.9 2004-03-28 07:32:23 mose Exp $

// Copyright (c) 2002-2004, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once ('tiki-setup.php');

include_once ('lib/articles/artlib.php');

if ($feature_articles != 'y') {
	$smarty->assign('msg', tra("This feature is disabled").": feature_articles");

	$smarty->display("error.tpl");
	die;
}

// PERMISSIONS: NEEDS p_admin
if ($tiki_p_admin_cms != 'y') {
	$smarty->assign('msg', tra("You dont have permission to use this feature"));

	$smarty->display("error.tpl");
	die;
}

if(isset($_REQUEST["add_type"])) {
	$artlib->add_type($_REQUEST["new_type"]);
}
elseif(isset($_REQUEST["remove_type"])) {
	$artlib->remove_type($_REQUEST["remove_type"]);
}
elseif(isset($_REQUEST["update_type"])) {
	foreach(array_keys($_REQUEST["type_array"]) as $this_type) {
		if (!isset($_REQUEST["use_ratings"][$this_type])) {$_REQUEST["use_ratings"][$this_type] = 'n';}
		if (!isset($_REQUEST["show_pre_publ"][$this_type])) {$_REQUEST["show_pre_publ"][$this_type] = 'n';}
		if (!isset($_REQUEST["show_post_expire"][$this_type])) {$_REQUEST["show_post_expire"][$this_type] = 'n';}
		if (!isset($_REQUEST["heading_only"][$this_type])) {$_REQUEST["heading_only"][$this_type] = 'n';}
		if (!isset($_REQUEST["allow_comments"][$this_type])) {$_REQUEST["allow_comments"][$this_type] = 'n';}
		if (!isset($_REQUEST["comment_can_rate_article"][$this_type])) {$_REQUEST["comment_can_rate_article"][$this_type] = 'n';}
		if (!isset($_REQUEST["show_image"][$this_type])) {$_REQUEST["show_image"][$this_type] = 'n';}
		if (!isset($_REQUEST["show_avatar"][$this_type])) {$_REQUEST["show_avatar"][$this_type] = 'n';}
		if (!isset($_REQUEST["show_author"][$this_type])) {$_REQUEST["show_author"][$this_type] = 'n';}
		if (!isset($_REQUEST["show_pubdate"][$this_type])) {$_REQUEST["show_pubdate"][$this_type] = 'n';}
		if (!isset($_REQUEST["show_expdate"][$this_type])) {$_REQUEST["show_expdate"][$this_type] = 'n';}
		if (!isset($_REQUEST["show_reads"][$this_type])) {$_REQUEST["show_reads"][$this_type] = 'n';}
		if (!isset($_REQUEST["show_size"][$this_type])) {$_REQUEST["show_size"][$this_type] = 'n';}
		if (!isset($_REQUEST["creator_edit"][$this_type])) {$_REQUEST["creator_edit"][$this_type] = 'n';}
		$artlib->edit_type($this_type, 
				$_REQUEST["use_ratings"][$this_type], 
				$_REQUEST["show_pre_publ"][$this_type], 
				$_REQUEST["show_post_expire"][$this_type], 
				$_REQUEST["heading_only"][$this_type], 
				$_REQUEST["allow_comments"][$this_type], 
				$_REQUEST["comment_can_rate_article"][$this_type], 
				$_REQUEST["show_image"][$this_type], 
				$_REQUEST["show_avatar"][$this_type], 
				$_REQUEST["show_author"][$this_type], 
				$_REQUEST["show_pubdate"][$this_type], 
				$_REQUEST["show_expdate"][$this_type], 
				$_REQUEST["show_reads"][$this_type], 
				$_REQUEST["show_size"][$this_type], 
				$_REQUEST["creator_edit"][$this_type]);
	}
}

$types = $artlib->list_types();
$smarty->assign('types', $types);

$smarty->assign('mid', 'tiki-article_types.tpl');
$smarty->display("tiki.tpl");

?>
