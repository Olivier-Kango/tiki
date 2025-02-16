<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
        'delete'                   => 'word',                //post
        'duplicate'                => 'bool',                //post
        'toGroup'                  => 'word',                //post
        'objectPerm'               => 'word',                //post
        ],
        'staticKeyFiltersForArrays' => [
            'filterGroup'           => 'groupname',          //post
            'groupPerm'             => 'bool',               //post
        ],
    ],
];
include_once('tiki-setup.php');
$access->check_permission('tiki_p_admin');
$all_perms = $userlib->get_permissions();

/**
 * @param $permName
 * @param $objectType
 * @return bool
 */
function is_perm($permName, $objectType)
{
    global $all_perms, $tikilib;
    $permGroup = $tikilib->get_permGroup_from_objectType($objectType);
    foreach ($all_perms['data'] as $perm) {
        if ($perm['permName'] == $permName) {
            return $permGroup == $perm['type'];
        }
    }
    return false;
}

/**
 * @param $objectId
 * @param $objectType
 * @param $objectName
 * @param string $filterGroup
 * @return array
 */
function list_perms($objectId, $objectType, $objectName, $filterGroup = '')
{
    global $prefs;
    $userlib = TikiLib::lib('user');
    $ret = [];
    $cats = [];
    $perms = $userlib->get_object_permissions($objectId, $objectType);
    if (! empty($perms)) {
        foreach ($perms as $perm) {
            if (empty($filterGroup) || in_array($perm['groupName'], $filterGroup)) {
                $json = json_encode(['group' => $perm['groupName'], 'perm' => $perm['permName'], 'objectId' => $objectId, 'objectType' => $objectType]);
                $ret[] = ['group' => $perm['groupName'], 'perm' => $perm['permName'], 'reason' => 'Object',
                           'objectId' => $objectId, 'objectType' => $objectType, 'objectName' => $objectName, 'json' => $json];
            }
        }
    }
    if ($prefs['feature_categories'] == 'y') {
        $categlib = TikiLib::lib('categ');
        $categs = $categlib->get_object_categories($objectType, $objectId);
        if (! empty($categs)) {
            foreach ($categs as $categId) {
                $category_perms = $userlib->get_object_permissions($categId, 'category');
                if (! empty($category_perms)) {
                    foreach ($category_perms as $category_perm) {
                        if (is_perm($category_perm['permName'], $objectType) && (empty($filterGroup) || in_array($category_perm['groupName'], $filterGroup))) {
                            $cats[] = ['group' => $category_perm['groupName'], 'perm' => $category_perm['permName'],
                                    'reason' => 'Category', 'objectId' => $categId, 'objectType' => 'category',
                                    'objectName' => $categlib->get_category_name($categId)];
                        }
                    }
                }
            }
        }
    }
    return ['objectId' => $objectId, 'special' => $ret, 'category' => $cats];
}

$filterGroup = empty($_REQUEST['filterGroup']) ? [] : $_REQUEST['filterGroup'];
$feedbacks = [];
$del = ! empty($_REQUEST['delete']) && $_REQUEST['delete'] === 'delete';
$dup = ! empty($_REQUEST['duplicate']) && $_REQUEST['duplicate'] === 'duplicate';
if ($del || $dup) {
    $access->checkCsrf();
    if (! empty($_REQUEST['groupPerm'])) {
        foreach ($_REQUEST['groupPerm'] as $perm) {
            $perm = json_decode($perm, true);
            if ($del) {
                $userlib->remove_permission_from_group($perm['perm'], $perm['group']);
                $feedbacks[] = tra('Remove permission %0 from %1', '', false, [$perm['perm'], $perm['group']]);
            } elseif (! empty($_REQUEST['toGroup']) && $userlib->group_exists($_REQUEST['toGroup'])) {
                $userlib->assign_permission_to_group($perm['perm'], $_REQUEST['toGroup']);
                $feedbacks[] = tra('Assign permission %0 to %1', '', false, [$perm['perm'], $_REQUEST['toGroup']]);
            }
        }
    }
    if (! empty($_REQUEST['objectPerm'])) {
        foreach ($_REQUEST['objectPerm'] as $perm) {
            $perm = json_decode($perm, true);
            if ($del) {
                $userlib->remove_object_permission($perm['group'], $perm['objectId'], $perm['objectType'], $perm['perm']);
                $feedbacks[] = tra('Remove permission %0 from %1', '', false, [$perm['perm'], $perm['group']]);
            } elseif (! empty($_REQUEST['toGroup']) && $userlib->group_exists($_REQUEST['toGroup'])) {
                $userlib->assign_object_permission($_REQUEST['toGroup'], $perm['objectId'], $perm['objectType'], $perm['perm']);
                $feedbacks[] = tra('Assign permission %0 to %1', '', false, [$perm['perm'], $_REQUEST['toGroup']]);
            }
        }
    }
    if (! empty($feedbacks) && $dup && ! empty($_REQUEST['toGroup']) && ! empty($filterGroup) && ! in_array($_REQUEST['toGroup'], $filterGroup)) {
        $filterGroup[] = $_REQUEST['toGroup'];
    }
}

$types = ['wiki page', 'file gallery', 'tracker', 'forum', 'group', 'articles', 'blog', 'calendar', 'sheet'];
$commentslib = TikiLib::lib('comments');
$all_groups = $userlib->list_all_groups();
$res = [];
foreach ($types as $type) {
    $res[$type]['default'] = [];
    $type_perms = $userlib->get_permissions(0, -1, 'permName_asc', '', $tikilib->get_permGroup_from_objectType($type));
    foreach ($all_groups as $gr) {
        $perms = $userlib->get_group_permissions($gr);
        foreach ($type_perms['data'] as $type_perm) {
            if (in_array($type_perm['permName'], $perms) && (empty($filterGroup) || in_array($gr, $filterGroup))) {
                $res[$type]['default'][] = ['group' => $gr, 'perm' => $type_perm['permName']];
            }
        }
    }
    $res[$type]['objects'] = [];
    $res[$type]['category'] = [];
    switch ($type) {
        case 'wiki page':
        case 'wiki':
            $objects = $tikilib->list_pageNames();
            foreach ($objects['data'] as $object) {
                $r = list_perms($object['pageName'], $type, $object['pageName'], $filterGroup);
                if (count($r['special']) > 0) {
                    $res[$type]['objects'][] = ['objectId' => $r['objectId'], 'special' => $r['special'], 'objectType' => $type];
                }
                if (count($r['category']) > 0) {
                    $res[$type]['category'][] = ['objectId' => $r['objectId'], 'category' => $r['category'], 'objectType' => $type];
                }
            }
            break;

        case 'file galleries':
        case 'file gallery':
            $filegallib = TikiLib::lib('filegal');
            $objects = $filegallib->list_file_galleries(0, -1, 'name_asc', '', '', $prefs['fgal_root_id']);
            foreach ($objects['data'] as $object) {
                $r = list_perms($object['id'], $type, $object['name'], $filterGroup);
                if (count($r['special']) > 0) {
                    $res[$type]['objects'][] = ['objectId' => $r['objectId'], 'special' => $r['special'], 'objectName' => $object['name'], 'objectType' => $type];
                }
                if (count($r['category']) > 0) {
                    $res[$type]['category'][] = ['objectId' => $r['objectId'], 'category' => $r['category'], 'objectName' => $object['name'], 'objectType' => $type];
                }
            }
            break;

        case 'tracker':
        case 'trackers':
            $objects = TikiLib::lib('trk')->list_trackers();
            foreach ($objects['data'] as $object) {
                $r = list_perms($object['trackerId'], $type, $object['name'], $filterGroup);
                if (count($r['special']) > 0) {
                    $res[$type]['objects'][] = ['objectId' => $r['objectId'], 'special' => $r['special'], 'objectName' => $object['name'], 'objectType' => $type];
                }
                if (count($r['category']) > 0) {
                    $res[$type]['category'][] = ['objectId' => $r['objectId'], 'category' => $r['category'], 'objectName' => $object['name'], 'objectType' => $type];
                }
            }
            break;

        case 'forum':
        case 'forums':
            $objects = $commentslib->list_forums();
            foreach ($objects['data'] as $object) {
                $r = list_perms($object['forumId'], $type, $object['name'], $filterGroup);
                if (count($r['special']) > 0) {
                    $res[$type]['objects'][] = ['objectId' => $r['objectId'], 'special' => $r['special'], 'objectName' => $object['name'], 'objectType' => $type];
                }
                if (count($r['category']) > 0) {
                    $res[$type]['category'][] = ['objectId' => $r['objectId'], 'category' => $r['category'], 'objectName' => $object['name'], 'objectType' => $type];
                }
            }
            break;

        case 'group':
        case 'groups':
            foreach ($all_groups as $object) {
                $r = list_perms($object, $type, '', $filterGroup);
                if (count($r['special']) > 0) {
                    $res[$type]['objects'][] = ['objectId' => $r['objectId'], 'special' => $r['special'], 'objectType' => $type];
                }
                if (count($r['category']) > 0) {
                    $res[$type]['category'][] = ['objectId' => $r['objectId'], 'category' => $r['category'], 'objectType' => $type];
                }
            }
            break;

        case 'calendar':
            $calendarlib = TikiLib::lib('calendar');
            $objects = $calendarlib->list_calendars();
            foreach ($objects['data'] as $object) {
                $r = list_perms($object['calendarId'], $type, $object['name'], $filterGroup);
                if (count($r['special']) > 0) {
                    $res[$type]['objects'][] = ['objectId' => $r['objectId'], 'special' => $r['special'], 'objectName' => $object['name'], 'objectType' => $type];
                }
                if (count($r['category']) > 0) {
                    $res[$type]['category'][] = ['objectId' => $r['objectId'], 'category' => $r['category'], 'objectName' => $object['name'], 'objectType' => $type];
                }
            }
            break;

        case 'articles':
            $artlib = TikiLib::lib('art');
            $objects = $artlib->list_articles();
            foreach ($objects['data'] as $object) {
                $r = list_perms($object['articleId'], $type, $object['title'], $filterGroup);
                if (count($r['special']) > 0) {
                    $res[$type]['objects'][] = ['objectId' => $r['objectId'], 'special' => $r['special'], 'objectName' => $object['title'], 'objectType' => $type];
                }
                if (count($r['category']) > 0) {
                    $res[$type]['category'][] = ['objectId' => $r['objectId'], 'category' => $r['category'], 'objectName' => $object['title'], 'objectType' => $type];
                }
            }
            break;

        case 'blog':
            $bloglib = TikiLib::lib('blog');
            $objects = $bloglib->list_blogs();

            foreach ($objects['data'] as $object) {
                $objectName = isset($object['name']) ? $object['name'] : null;
                $r = list_perms($object['blogId'], $type, $objectName, $filterGroup);
                if (count($r['special']) > 0) {
                    $res[$type]['objects'][] = ['objectId' => $r['objectId'], 'special' => $r['special'], 'objectName' => $objectName, 'objectType' => $type];
                }
                if (count($r['category']) > 0) {
                    $res[$type]['category'][] = ['objectId' => $r['objectId'], 'category' => $r['category'], 'objectName' => $objectName, 'objectType' => $type];
                }
            }
            break;

        case 'sheet':
            $sheetlib = TikiLib::lib('sheet');
            $objects = $sheetlib->list_sheets();
            foreach ($objects['data'] as $object) {
                $r = list_perms($object['sheetId'], $type, isset($object['name']) ? $object['name'] : null, $filterGroup);
                if (count($r['special']) > 0) {
                    $res[$type]['objects'][] = ['objectId' => $r['objectId'], 'special' => $r['special'], 'objectName' => $object['name'], 'objectType' => $type];
                }
                if (count($r['category']) > 0) {
                    $res[$type]['category'][] = ['objectId' => $r['objectId'], 'category' => $r['category'], 'objectName' => $object['name'], 'objectType' => $type];
                }
            }
            break;

        default:
            break;
    }
}
if (count($feedbacks) > 0) {
    Feedback::note(['mes' => $feedbacks]);
}
$smarty->assign_by_ref('res', $res);
$smarty->assign_by_ref('filterGroup', $filterGroup);
$smarty->assign_by_ref('all_groups', $all_groups);

$smarty->assign('mid', 'tiki-list_object_permissions.tpl');
$smarty->display('tiki.tpl');
