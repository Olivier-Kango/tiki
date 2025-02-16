<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
            'username'             => 'digits',            //post
            'path'                 => 'bool',              //post
            'site'                 => 'digits',            //post
            'password'             => 'password',          //post
            'dbg'                  => 'bool',              //post
            'find'                 => 'word',              //post
            'addpage'              => 'bool',              //post
            'pageName'             => 'pagename',          //post
            'clearpages'           => 'bool',              //post
            'addstructure'         => 'bool',              //post
            'structure'            => 'string',            //post
            'clearstructures'      => 'bool',              //post
            'addarticle'           => 'bool',              //post
            'articleId'            => 'int',               //post
            'send'                 => 'bool',              //post
        ],
        'staticKeyFiltersForArrays' => [
            'sendpages'            => 'string',            //post
            'sendstructures'       => 'string',            //post
            'sendarticles'         => 'string',            //post
        ],
    ],
];


require_once('tiki-setup.php');
use PhpXmlRpc\Value as XML_RPC_Value;
use PhpXmlRpc\Request as XML_RPC_Message;
use PhpXmlRpc\Client as XML_RPC_Client;

$structlib = TikiLib::lib('struct');

//get_strings tra("Send Pages");
$access->check_feature('feature_comm');
$access->check_permission_either(['tiki_p_send_pages', 'tiki_p_send_articles']);

if ($tiki_p_send_pages != 'y' && $tiki_p_send_articles != 'y') {
    $smarty->assign('errortype', 401);
    $smarty->assign('msg', tra('You do not have the permission that is needed to use this feature'));
    $smarty->display('error.tpl');
    die;
}

if (! isset($_REQUEST['username'])) {
    $_REQUEST['username'] = $user;
}

if (! isset($_REQUEST['path'])) {
    $_REQUEST['path'] = '/tiki/commxmlrpc.php';
}

if (! isset($_REQUEST['site'])) {
    $_REQUEST['site'] = '';
}

if (! isset($_REQUEST['password'])) {
    $_REQUEST['password'] = '';
}

if (! isset($_REQUEST['sendpages'])) {
    $sendpages = [];
} else {
    $sendpages = json_decode(urldecode($_REQUEST['sendpages']));
}

if (! isset($_REQUEST['sendstructures'])) {
    $sendstructures = [];
} else {
    $sendstructures = json_decode(urldecode($_REQUEST['sendstructures']));
}

if (! isset($_REQUEST['sendarticles'])) {
    $sendarticles = [];
} else {
    $sendarticles = json_decode(urldecode($_REQUEST['sendarticles']));
}

$smarty->assign('username', $_REQUEST['username']);
$smarty->assign('site', $_REQUEST['site']);
$smarty->assign('path', $_REQUEST['path']);
$smarty->assign('password', $_REQUEST['password']);

if (isset($_REQUEST['dbg'])) {
    $smarty->assign('dbg', $_REQUEST['dbg']);
}

if (isset($_REQUEST['find'])) {
    $find = $_REQUEST['find'];
} else {
    $find = '';
}

$smarty->assign('find', $find);

if (isset($_REQUEST['addpage'])) {
    if (! in_array($_REQUEST['pageName'], $sendpages)) {
        $sendpages[] = $_REQUEST['pageName'];
    }
}

if (isset($_REQUEST['clearpages'])) {
    $sendpages = [];
}

if (isset($_REQUEST['addstructure'])) {
    if (! in_array($_REQUEST['structure'], $sendstructures)) {
        $sendstructures[] = $_REQUEST['structure'];
    }
}

if (isset($_REQUEST['clearstructures'])) {
    $sendstructures = [];
}

if (isset($_REQUEST['addarticle'])) {
    if (! in_array($_REQUEST['articleId'], $sendarticles)) {
        $sendarticles[] = $_REQUEST['articleId'];
    }
}

if (isset($_REQUEST['cleararticles'])) {
    $sendarticles = [];
}
$structures = $structlib->list_structures(0, -1, 'pageName_asc', $find);
$smarty->assign_by_ref('structures', $structures['data']);
$msg = '';

if (isset($_REQUEST['send'])) {
    $access->checkCsrf();
    // Create XMLRPC object
    $_REQUEST['path'] = preg_replace('/^\/?/', '/', $_REQUEST['path']);
    $_REQUEST['site'] = parse_url($_REQUEST['site'], PHP_URL_HOST);
    $client = new XML_RPC_Client($_REQUEST['path'], $_REQUEST['site'], 80);
    $client->return_type = 'xml';
    $client->setDebug((isset($_REQUEST['dbg']) && $_REQUEST['dbg'] == 'on') ? true : false);
    foreach ($sendstructures as $structure) {
        $spages = $structlib->s_get_structure_pages($structure);
        $pos = 0;
        foreach ($spages as $spage) {
            $listPageNames[$spage['page_ref_id']] = $spage['pageName'];
            $page_info = $tikilib->get_page_info($spage['pageName']);
            $pos++;
            $searchMsg = new XML_RPC_Message(
                'sendStructurePage',
                [
                    new XML_RPC_Value($_SERVER['SERVER_NAME'], 'string'),
                    new XML_RPC_Value($_REQUEST['username'], 'string'),
                    new XML_RPC_Value($_REQUEST['password'], 'string'),
                    new XML_RPC_Value($spages[0]['pageName'], 'string'),
                    new XML_RPC_Value($spage['parent_id'] ? $listPageNames[$spage['parent_id']] : $spage['pageName'], 'string'),
                    new XML_RPC_Value($spage['pageName'], 'string'),
                    new XML_RPC_Value(base64_encode($page_info['data']), 'string'),
                    new XML_RPC_Value($page_info['comment'], 'string'),
                    new XML_RPC_Value($page_info['description'], 'string'),
                    new XML_RPC_Value($pos, 'string'),
                    new XML_RPC_Value($spage['page_alias'], 'string')
                ]
            );
            $result = $client->send($searchMsg);
            if (! $result) {
                $errorMsg = tra('Cannot login to server maybe the server is down');
                $msg .= $errorMsg;
            } else {
                if (! $result->faultCode()) {
                    $msg .= tra('Page') . ': ' . $spage['pageName'] . ' ' . tra('successfully sent') . '<br />';
                } else {
                    $errorMsg = $result->faultString();
                    $msg .= tra('Page') . ': ' . $spage['pageName'] . ' ' . tra('not sent') . '!' . '<br />';
                    $msg .= tra('Error: ') . $result->faultCode() . '-' . tra($errorMsg) . '<br />';
                }
            }
        }
    }

    foreach ($sendpages as $page) {
        $page_info = $tikilib->get_page_info($page);
        if ($page_info) {
            $searchMsg = new XML_RPC_Message(
                'sendPage',
                [
                    new XML_RPC_Value($_SERVER['SERVER_NAME'], 'string'),
                    new XML_RPC_Value($_REQUEST['username'], 'string'),
                    new XML_RPC_Value($_REQUEST['password'], 'string'),
                    new XML_RPC_Value($page, 'string'),
                    new XML_RPC_Value(base64_encode($page_info['data']), 'string'),
                    new XML_RPC_Value($page_info['comment'], 'string'),
                    new XML_RPC_Value($page_info['description'], 'string'),
                ]
            );
            $result = $client->send($searchMsg);
            if (! $result) {
                $errorMsg = tra('Cannot login to server maybe the server is down');
                $msg .= $errorMsg;
            } else {
                if (! $result->faultCode()) {
                    $msg .= tra('Page') . ': ' . $page . ' ' . tra('successfully sent') . '<br />';
                } else {
                    $errorMsg = $result->faultString();
                    $msg .= tra('Page') . ': ' . $page . ' ' . tra('not sent') . '!' . '<br />';
                    $msg .= tra('Error: ') . $result->faultCode() . '-' . tra($errorMsg) . '<br />';
                }
            }
        }
    }
    $artlib = TikiLib::lib('art');
    foreach ($sendarticles as $article) {
        $page_info = $artlib->get_article($article);
        if ($page_info) {
            $searchMsg = new XML_RPC_Message(
                'sendArticle',
                [
                    new XML_RPC_Value($_SERVER['SERVER_NAME'], 'string'),
                    new XML_RPC_Value($_REQUEST['username'], 'string'),
                    new XML_RPC_Value($_REQUEST['password'], 'string'),
                    new XML_RPC_Value(base64_encode($page_info['title']), 'string'),
                    new XML_RPC_Value(base64_encode($page_info['authorName']), 'string'),
                    new XML_RPC_Value($page_info['size'], 'int'),
                    new XML_RPC_Value($page_info['useImage'], 'string'),
                    new XML_RPC_Value($page_info['image_name'], 'string'),
                    new XML_RPC_Value($page_info['image_type'], 'string'),
                    new XML_RPC_Value($page_info['image_size'], 'int'),
                    new XML_RPC_Value($page_info['image_x'], 'int'),
                    new XML_RPC_Value($page_info['image_y'], 'int'),
                    new XML_RPC_Value(base64_encode($page_info['image_data']), 'string'),
                    new XML_RPC_Value($page_info['publishDate'], 'int'),
                    new XML_RPC_Value($page_info['expireDate'], 'int'),
                    new XML_RPC_Value($page_info['created'], 'int'),
                    new XML_RPC_Value(base64_encode($page_info['heading']), 'string'),
                    new XML_RPC_Value(base64_encode($page_info['body']), 'string'),
                    new XML_RPC_Value($page_info['hash'], 'string'),
                    new XML_RPC_Value($page_info['author'], 'string'),
                    new XML_RPC_Value($page_info['type'], 'string'),
                    new XML_RPC_Value($page_info['rating'], 'string')
                ]
            );
            $result = $client->send($searchMsg);
            if (! $result) {
                $errorMsg = tra('Cannot login to server maybe the server is down');
                $msg .= $errorMsg;
            } else {
                if (! $result->faultCode()) {
                    $msg .= tra('Article:') . ' ' . $page_info['title'] . ' ' . tra('successfully sent') . '<br />';
                } else {
                    $errorMsg = $result->faultString();
                    $msg .= tra('Article:') . ' ' . $page_info['title'] . ' ' . tra('not sent') . '!' . '<br />';
                    $msg .= tra('Error: ') . $result->faultCode() . '-' . tra($errorMsg) . '<br />';
                }
            }
        }
    }
}
$smarty->assign('msg', $msg);
$smarty->assign('sendpages', $sendpages);
$sendstructures_names = [];
foreach ($sendstructures as $key => $id) {
    foreach ($structures['data'] as $structure) {
        if ($structure['page_ref_id'] == $id) {
            $sendstructures_names[$key] = $structure['pageName'];
        }
    }
}
$smarty->assign('sendstructures_names', $sendstructures_names);
$smarty->assign('sendarticles', $sendarticles);
$form_sendpages = urlencode(json_encode($sendpages));
$form_sendstructures = urlencode(json_encode($sendstructures));
$form_sendarticles = urlencode(json_encode($sendarticles));
$smarty->assign('form_sendpages', $form_sendpages);
$smarty->assign('form_sendstructures', $form_sendstructures);
$smarty->assign('form_sendarticles', $form_sendarticles);
$pages = $tikilib->list_pageNames(0, -1, 'pageName_asc', $find);
$smarty->assign('pages', $pages['data']);

if ($prefs['feature_articles'] == 'y') {
    $artlib = TikiLib::lib('art');
    $articles = $artlib->list_articles(0, -1, 'publishDate_desc', $find, 0, $tikilib->now, $user);
    $smarty->assign('articles', $articles['data']);
}

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

$smarty->assign('mid', 'tiki-send_objects.tpl');
$smarty->display('tiki.tpl');
