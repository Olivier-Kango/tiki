<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('tiki-setup.php');

global $user;
$relationlib = TikiLib::lib('relation');
$artlib = TikiLib::lib('art');
$trackerlib = TikiLib::lib('trk');

if ($prefs['user_favorites'] !== 'y') {
    throw new Services_Exception(tr('Feature disabled'), 403);
}

if (! $user) {
    return [];
}


$wikiPages = [];
$articles = [];
$trackersItem = [];

foreach ($relationlib->get_relations_from('user', $user, 'tiki.user.favorite') as $relation) {
    if ($relation['type'] == "wiki page") {
        $wikiPages[] = $relation['itemId'];
    } elseif ($relation['type'] == "article") {
        $articles[] = [
            'id' => $relation['itemId'],
            'title' => $artlib->get_title($relation['itemId'])
            ];
    } elseif ($relation['type'] == "trackeritem") {
        $trackersItem[] = [
            'id' => $relation['itemId'],
            'title' => $trackerlib->get_title_sefurl($relation['itemId'])];
    }
}

$smarty->assign('wikiPages', $wikiPages);
$smarty->assign('articles', $articles);
$smarty->assign('trackersItem', $trackersItem);
$smarty->assign('mid', 'tiki-user_favorites.tpl');
$smarty->display("tiki.tpl");
