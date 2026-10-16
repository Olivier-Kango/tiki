<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

$inputConfiguration = [
    [
        'staticKeyFilters' => [
            'trackerId' => 'int',
        ],
    ],
];
require_once 'tiki-setup.php';
use Tiki\Sections;

$section = Sections::SECTION_TRACKERS;
Sections::setCurrentSection($section);

$trklib = TikiLib::lib('trk');

$access->check_feature('feature_trackers');

if (empty($_REQUEST['trackerId'])) {
    Feedback::errorAndDie(tra('No tracker indicated'), \Laminas\Http\Response::STATUS_CODE_409);
}

$trackerId = (int) $_REQUEST['trackerId'];

$tracker_info = $trklib->get_tracker($trackerId);
if (! $tracker_info) {
    Feedback::errorAndDie(tra('Tracker not found'), \Laminas\Http\Response::STATUS_CODE_404);
}
if ($opts = $trklib->get_tracker_options($trackerId)) {
    $tracker_info = array_merge($tracker_info, $opts);
}

$access->check_permission('tiki_p_admin_trackers', tra('Administer tracker'), 'tracker', $trackerId);

// 1. Permission source: 'object' (individual), 'category', or 'global'
$tracker_perms_obj = Perms::get('tracker', $trackerId);
$perms_from = $tracker_perms_obj->from() ?: 'global';

// Object-level group permissions (only populated when individual perms are set)
$object_permissions = [];
if ($perms_from === 'object') {
    foreach ($userlib->get_object_permissions($trackerId, 'tracker') as $row) {
        $object_permissions[$row['permName']][] = $row['groupName'];
    }
}

// Permission labels come from the core permission definitions, so they stay in sync with Tiki
$tracker_perm_labels = array_column(
    $userlib->get_permissions(0, -1, 'permName_asc', '', 'trackers')['data'],
    'permDesc',
    'permName'
);

// 2. Ownership / writer special rules from tracker options
$special_rules = [
    [
        'key'   => 'userCanSeeOwn',
        'label' => tra('Creators can see their own items'),
        'value' => $tracker_info['userCanSeeOwn'] ?? 'n',
    ],
    [
        'key'   => 'groupCanSeeOwn',
        'label' => tra('Creator\'s group members can see the item'),
        'value' => $tracker_info['groupCanSeeOwn'] ?? 'n',
    ],
    [
        'key'   => 'writerCanModify',
        'label' => tra('Creators can edit their own items'),
        'value' => $tracker_info['writerCanModify'] ?? 'n',
    ],
    [
        'key'   => 'writerCanRemove',
        'label' => tra('Creators can delete their own items'),
        'value' => $tracker_info['writerCanRemove'] ?? 'n',
    ],
    [
        'key'   => 'writerGroupCanModify',
        'label' => tra('Creator\'s group can edit the item'),
        'value' => $tracker_info['writerGroupCanModify'] ?? 'n',
    ],
    [
        'key'   => 'writerGroupCanRemove',
        'label' => tra('Creator\'s group can delete the item'),
        'value' => $tracker_info['writerGroupCanRemove'] ?? 'n',
    ],
    [
        'key'   => 'adminOnlyViewEditItem',
        'label' => tra('Items accessible only via wiki pages (direct access restricted to admins)'),
        'value' => $tracker_info['adminOnlyViewEditItem'] ?? 'n',
    ],
];

// 3. Fields with access restrictions and fields that trigger local notifications
$all_fields_result = $trklib->list_tracker_fields($trackerId, 0, -1);
$restricted_fields  = [];
$notification_fields = [];

foreach ($all_fields_result['data'] as $field) {
    if (! empty($field['isHidden']) || ! empty($field['visibleBy']) || ! empty($field['editableBy'])) {
        $restricted_fields[] = $field;
    }

    if ($field['type'] === 'u') {
        $notify = (int) ($field['options_map']['notify'] ?? 0);
        if ($notify > 0) {
            $field['_notify_mode']    = $notify === 1 ? tra('Always (including self)') : tra('Always (excluding self)');
            $field['_notify_trigger'] = tra('Any item change');
            $notification_fields[] = $field;
        }
    } elseif ($field['type'] === 'm') {
        $triggers = [];
        if (! empty($field['options_map']['watchopen'])) {
            $triggers[] = tra('→ Open');
        }
        if (! empty($field['options_map']['watchpending'])) {
            $triggers[] = tra('→ Pending');
        }
        if (! empty($field['options_map']['watchclosed'])) {
            $triggers[] = tra('→ Closed');
        }
        if (count($triggers) > 0) {
            $field['_notify_mode']    = tra('Status change');
            $field['_notify_trigger'] = implode(', ', $triggers);
            $notification_fields[] = $field;
        }
    }
}

// Comments section: pre-build the "additional rules" lists to avoid punctuation outside {tr} in template
$view_comment_additional_parts = [];
if (! empty($tracker_info['userCanSeeOwn']) && $tracker_info['userCanSeeOwn'] === 'y') {
    $view_comment_additional_parts[] = tra('item creator');
}
if (! empty($tracker_info['groupCanSeeOwn']) && $tracker_info['groupCanSeeOwn'] === 'y') {
    $view_comment_additional_parts[] = tra("creator's group member");
}
$view_comment_additional = implode(', ', $view_comment_additional_parts);

$post_comment_additional_parts = [];
if (! empty($tracker_info['userCanSeeOwn']) && $tracker_info['userCanSeeOwn'] === 'y') {
    $post_comment_additional_parts[] = tra('item creator');
}
if (! empty($tracker_info['writerCanModify']) && $tracker_info['writerCanModify'] === 'y') {
    $post_comment_additional_parts[] = tra('creator can modify');
}
if (! empty($tracker_info['writerGroupCanModify']) && $tracker_info['writerGroupCanModify'] === 'y') {
    $post_comment_additional_parts[] = tra("creator's group can modify");
}
$post_comment_additional = implode(', ', $post_comment_additional_parts);

// 4. Notification watchers: group-level watches and outbound emails on this tracker
$group_watches = $tikilib->get_groups_watching($trackerId, 'tracker_modified');

$outbound_emails = [];
if (! empty($tracker_info['outboundEmail'])) {
    $outbound_emails = array_values(array_filter(array_map('trim', explode(',', $tracker_info['outboundEmail']))));
}

$smarty->assign('tracker_info', $tracker_info);
$smarty->assign('trackerId', $trackerId);
$smarty->assign('perms_from', $perms_from);
$smarty->assign('object_permissions', $object_permissions);
$smarty->assign('tracker_perm_labels', $tracker_perm_labels);
$smarty->assign('special_rules', $special_rules);
$smarty->assign('restricted_fields', $restricted_fields);
$smarty->assign('notification_fields', $notification_fields);
$smarty->assign('view_comment_additional', $view_comment_additional);
$smarty->assign('post_comment_additional', $post_comment_additional);
$smarty->assign('group_watches', $group_watches);
$smarty->assign('outbound_emails', $outbound_emails);

$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->display('tiki-tracker_overview.tpl');
