<?php
// (c) Copyright 2002-2010 by authors of the Tiki Wiki/CMS/Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_listpages_help() {
	$help = tra("List wiki pages.");
	$help .= "<br />";
	$help .= "~np~{LISTPAGES(initial=txt, showNameOnly=y|n, categId=id, structHead=y|n, showPageAlias=y|n, offset=num, max=num, find=txt, exact_match=y|n, only_orphan_pages=y|n, for_list_pages=y|n)}{LISTPAGES}~/np~";

	return $help;
}

function wikiplugin_listpages_info() {
	return array(
		'name' => tra('List Pages'),
		'documentation' => 'PluginListpages',
		'description' => tra('List wiki pages.'),
		'prefs' => array('wikiplugin_listpages'),
		'icon' => 'pics/icons/page_white_stack.png',
		'params' => array(
			'offset' => array(
				'required' => false,
				'name' => tra('Result Offset'),
				'description' => tra('Result number at which the listing should start.'),
				'filter' => 'int',
			),
			'max' => array(
				'required' => false,
				'name' => tra('Result Count'),
				'description' => tra('Number of results displayed in the list.'),
			),
			'initial' => array(
				'required' => false,
				'name' => tra('Initial'),
				'description' => tra('txt'),
			),
			'showNameOnly' => array(
				'required' => false,
				'name' => tra('Show Name Only'),
				'description' => 'y|n',
				'filter' => 'alpha',
			),
			'categId' => array(
				'required' => false,
				'name' => tra('Category'),
				'description' => tra('Category ID').' '.tra('For an OR between categories use : (ex:1:2). For and AND use + ex(1+2).'),
				'filter' => 'striptags',
			),
			'structHead' => array(
				'required' => false,
				'name' => tra('Structure Head'),
				'description' => 'y|n',
				'filter' => 'alpha',
			),
			'showPageAlias' => array(
				'required' => false,
				'name' => tra('Show Page Alias'),
				'description' => 'y|n',
				'filter' => 'alpha',
			),
			'find' => array(
				'required' => false,
				'name' => tra('Find'),
				'description' => tra('txt'),
			),
			'lang' => array(
				'required' => false,
				'name' => tra('Language'),
				'description' => tra('Two letter language code to filter pages listed.'),
			),
			'langOrphan' => array(
				'required' => false,
				'name' => tra('Orphan Language'),
				'description' => tra('Two letter language code to filter pages listed. Only pages not available in the provided language will be listed.'),
			),
			'translations' => array(
				'required' => false,
				'name' => tra('Load Translations'),
				'description' => tra('user or pipe separated list of two letter language codes for additional languages to display. If the language parameter is not defined, the first element of this list will be used as the primary filter.'),
			),
			'exact_match' => array(
				'required' => false,
				'name' => tra('Exact Match'),
				'description' => 'y|n'.' '.tra('Related to Find.'),
				'filter' => 'alpha',
			),
			'only_orphan_pages' => array(
				'required' => false,
				'name' => tra('Only Orphan Pages'),
				'description' => 'y|n',
				'filter' => 'alpha',
			),
			'for_list_pages' => array(
				'required' => false,
				'name' => tra('For List Pages'),
				'description' => 'y|n',
				'filter' => 'alpha',
			),
			'sort' => array(
				'required' => false,
				'name' => tra('Sort'),
				'description' => 'lastModif_desc'.tra('or').'pageName_asc',
			),
			'start' => array(
				'required' => false,
				'name' => tra('Start'),
				'description' => tra('When only a portion of the page should be included, specify the marker from which inclusion should start.'),
			),
			'end' => array(
				'required' => false,
				'name' => tra('Stop'),
				'description' => tra('When only a portion of the page should be included, specify the marker at which inclusion should end.'),
			),
			'length' => array(
				'required' => false,
				'name' => tra('Length'),
				'description' => tra('Number of characters to display'),
				'filter' => 'int',
			),
		),
	);
}

function wikiplugin_listpages($data, $params) {
	global $prefs, $tiki_p_view, $tikilib, $smarty;

	if ( isset($prefs) ) {
		// Handle 1.10.x prefs
		$feature_listPages = $prefs['feature_listPages'];
		$feature_wiki = $prefs['feature_wiki'];
	} else {
		// Handle 1.9.x prefs
		global $feature_listPages, $feature_wiki;
	}

	if ( $feature_wiki != 'y' || $feature_listPages != 'y' || $tiki_p_view != 'y') {
		// the feature is disabled or the user can't read wiki pages
		return '';
	}
	$default = array('offset'=>0, 'max'=>-1, 'sort'=>'pageName_asc', 'find'=>'', 'start'=>'', 'end'=>'', 'length'=>-1, 'translations'=>null);
	$params = array_merge($default, $params);
	extract($params,EXTR_SKIP);
	$filter = array();
	if (!isset($initial)) {
		if (isset($_REQUEST['initial'])) {
			$initial = $_REQUEST['initial'];
		} else {
			$initial = '';
		}
	}
	if (!empty($categId)) {
		if (strstr($categId, ':')) {
			$filter['categId'] = explode(':', $categId);
		} elseif (strstr($categId, '+')) {
			$filter['andCategId'] = explode('+', $categId);
		} else {
			$filter['categId'] = $categId;
		}
	}
	if (!empty($structHead) && $structHead == 'y') {
		$filter['structHead'] = $structHead;
	}
	if (!empty($translations) && $prefs['feature_multilingual'] == 'y') {
		global $multilinguallib;
		require_once 'lib/multilingual/multilinguallib.php';
		if ($translations == 'user') {
			$translations = $multilinguallib->preferredLangs();
		} else {
			$translations = explode( '|', $translations );
		}
	}
	if (!empty($langOrphan)) {
		$filter['langOrphan'] = $langOrphan;
	}
	if (!empty($lang)) {
		$filter['lang'] = $lang;
	} elseif (is_array($translations)) {
		$lang = $filter['lang'] = reset($translations);
	}
	$exact_match = ( isset($exact_match) && $exact_match == 'y' );
	$only_name = ( isset($showNameOnly) && $showNameOnly == 'y' );
	$only_orphan_pages = ( isset($only_orphan_pages) && $only_orphan_pages == 'y' );
	$for_list_pages = ( isset($for_list_pages) && $for_list_pages == 'y' );
	$only_cant = false;
	$listpages = $tikilib->list_pages($offset, $max, $sort, $find, $initial, $exact_match, $only_name, $for_list_pages, $only_orphan_pages, $filter, $only_cant);

	if ( is_array( $translations ) ) {
		$used = array();
		foreach( $listpages['data'] as &$page ) {
			$pages = $multilinguallib->getTranslations('wiki page', $page['page_id']);

			$page['translations'] = array();
			foreach( $pages as $trad )
				if( $trad['lang'] != $lang && in_array($trad['lang'], $translations) ) {
					$page['translations'][ $trad['lang'] ] = $trad['objName'];
					$used[$trad['lang']] = $trad['langName'];
				}
		}

		$smarty->assign( 'wplp_used', $used );
	}

	$smarty->assign_by_ref('listpages', $listpages['data']);
	if (!empty($showPageAlias) && $showPageAlias == 'y')
		$smarty->assign_by_ref('showPageAlias', $showPageAlias);
	if (isset($showNameOnly) && $showNameOnly == 'y') {
		$ret = $smarty->fetch('wiki-plugins/wikiplugin_listpagenames.tpl');
	} else {
		if (!empty($start) || !empty($end) || $length > 0) {
			foreach ($listpages['data'] as $i=>$page) {
				$listpages['data'][$i]['snippet'] = $tikilib->get_snippet($page['data'], $page['is_html'], '', $length, $start, $end);
			}
		}
		$ret = $smarty->fetch('tiki-listpages_content.tpl');
	}

	return '~np~'.$ret.'~/np~';
}
