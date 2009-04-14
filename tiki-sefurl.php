<?php

// Copyright (c) 2002-2008, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// Function created 2008-07-14 SEWilco (scot@wilcoxon.org)
// 2009-01-12 SEWilco (scot@wilcoxon.org) Modified for feature_sefurl_filter.
if (strpos($_SERVER["SCRIPT_NAME"],basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}
define('PATTERN_TO_CLEAN_TEXT', '/[^0-9a-zA-Z_]/');
function filter_out_sefurl($tpl_output, &$smarty, $type=null) {
	global $sefurl_regex_out, $tikilib, $prefs;

	if ($prefs['feature_sefurl'] != 'y') {
		return $tpl_output;
	}
	global  $cachelib; include_once('lib/cache/cachelib.php');
	if (!is_array($sefurl_regex_out)) {
		if (!$cachelib->isCached('sefurl_regex_out')) {
			$query = 'select * from `tiki_sefurl_regex_out` where `silent` != ? order by `order` asc';
			$result = $tikilib->query($query, array('y'));
			$sefurl_regex_out = array();
			if (!empty($result)) {
				while ($res = $result->fetchRow()) {
					if (empty($res['feature']) || $prefs[$res['feature']] == 'y') {
						$sefurl_regex_out[] = $res;
					}
				}
			}
			$cachelib->cacheItem('sefurl_regex_out',serialize($sefurl_regex_out));
		} else {
			$sefurl_regex_out = unserialize($cachelib->getCached('sefurl_regex_out'));
		}
	}
	if ($type == 'article' && $prefs['feature_sefurl_title_article'] == 'y') {
		global $artlib; include_once('lib/articles/artlib.php');
		if (preg_match('/articleId=([0-9]+)/', $tpl_output, $matches)) {
			$title = $artlib->get_title($matches[1]);
			$title = preg_replace(PATTERN_TO_CLEAN_TEXT, '-', $title);
			$tpl_output .= "/$title";
		}
	}
	if ($type == 'blog' && $prefs['feature_sefurl_title_blog'] == 'y') {
		global $bloglib; include_once('lib/blogs/bloglib.php');
		if (preg_match('/blogId=([0-9]+)/', $tpl_output, $matches)) {
			$title = $bloglib->get_title($matches[1]);
			$title = preg_replace(PATTERN_TO_CLEAN_TEXT, '-', $title);
			$tpl_output .= "/$title";
		}
	}
	foreach ($prefs['feature_sefurl_paths'] as $path) {
		if (isset($_REQUEST[$path])) {
			$tpl_output = $_REQUEST[$path]."/$tpl_output";
		}
	}
	foreach ($sefurl_regex_out as $regex) {
		if (empty($type) || $type == $regex['type']) {
			// if a question mark in pattern, deal with possible additional terms
			// The '?&' isn't pretty but seems to work.
			if( strpos($regex['left'],'?') !== FALSE ) {
				$tpl_output = preg_replace( '/'.$regex['left'].'&/', $regex['right'].'?&', $tpl_output );
			}
			$tpl_output = preg_replace( '/'.$regex['left'].'/', $regex['right'], $tpl_output );
		}
	}
	return urlencode($tpl_output);
}

