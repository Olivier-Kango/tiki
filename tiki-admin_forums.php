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
        'staticKeyFilters'                     => [
            'forumId'                          => 'int',        //post
            'parentId'                         => 'int',        //post
            'lock'                             => 'bool',        //post
            'useMail'                          => 'bool',        //post
            'usePruneUnreplied'                => 'bool',        //post
            'controlFlood'                     => 'bool',        //post
            'usePruneOld'                      => 'bool',        //post
            'vote_threads'                     => 'bool',        //post
            'outbound_mails_for_inbound_mails' => 'bool',        //post
            'outbound_mails_reply_link'        => 'bool',        //post
            'topics_list_reads'                => 'bool',        //post
            'topics_list_replies'              => 'bool',        //post
            'show_description'                 => 'bool',        //post
            'is_flat'                          => 'bool',        //post
            'topic_summary'                    => 'bool',        //post
            'topic_smileys'                    => 'bool',        //post
            'ui_avatar'                        => 'bool',        //post
            'ui_rating_choice_topic'           => 'bool',        //post
            'ui_flag'                          => 'bool',        //post
            'ui_email'                         => 'bool',        //post
            'ui_posts'                         => 'bool',        //post
            'ui_level'                         => 'bool',        //post
            'ui_online'                        => 'bool',        //post
            'topics_list_pts'                  => 'bool',        //post
            'topics_list_lastpost'             => 'bool',        //post
            'topics_list_lastpost_title'       => 'bool',        //post
            'topics_list_lastpost_avatar'      => 'bool',        //post
            'topics_list_author'               => 'bool',        //post
            'topics_list_author_avatar'        => 'bool',        //post
            'att_list_nb'                      => 'bool',        //post
            'threadOrdering'                   => 'striptags',   //post
            'threadStyle'                      => 'striptags',   //post
            'commentsPerPage'                  => 'striptags',   //post
            'image'                            => 'striptags',   //post
            'section'                          => 'striptags',   //post
            'new_section'                      => 'striptags',   //post
            'att_store_dir'                    => 'striptags',   //post
            'forumLanguage'                    => 'lang',        //post
            'floodInterval'                    => 'int',         //post
            'mail'                             => 'email',       //post
            'pruneUnrepliedAge'                => 'striptags',   //post
            'pruneMaxAge'                      => 'int',         //post
            'topicsPerPage'                    => 'int',         //post
            'topicOrdering'                    => 'striptags',   //post
            'approval_type'                    => 'striptags',   //post
            'att'                              => 'striptags',   //post
            'att_store'                        => 'striptags',   //post
            'att_max_size'                     => 'int',         //post
            'forum_last_n'                     => 'int',         //post
            'duplicate'                        => 'bool',        //post
            'duplicate_name'                   => 'striptags',   //post
            'duplicate_forumId'                => 'int',         //post
            'dupCateg'                         => 'bool',        //post
            'offset'                           => 'int',         //post
            'numrows'                          => 'int',         //post
            'dupPerms'                         => 'bool',        //post
            'name'                             => 'striptags',   //post
            'description'                      => 'xss',         //post
            'sort_mode'                        => 'striptags',   //get
            'dup_mode'                         => 'bool',        //post
            'find'                             => 'text',        //post
            'moderator '                       => 'text',        //post
            'moderator_group'                  => 'groupname',   //post
            'forum_password'                   => 'password',    //post
            'outbound_address'                 => 'text',        //post
            'outbound_from'                    => 'text',        //post
            'inbound_pop_server'               => 'text',        //post
            'inbound_pop_port'                 => 'text',        //post
            'inbound_pop_user'                 => 'text',        //post
            'inbound_pop_password'             => 'password',    //post
            'save'                             => 'bool',        //post
        ],
    ],
];
$section = 'admin';
require_once('tiki-setup.php');
if (! isset($_REQUEST['forumId'])) {
    $_REQUEST['forumId'] = 0;
}
if (! isset($_REQUEST['parentId'])) {
    $_REQUEST['parentId'] = 0;
}
$access->check_feature('feature_forums');

$objectperms = Perms::get('forum', $_REQUEST['forumId']);
if (! $objectperms->admin_forum) {
    $access->display_error('', tra('Permission denied') . ": " . 'tiki_p_admin_forum', '403');
}
$smarty->assign('permsType', $objectperms->from());

$auto_query_args = [
            'forumId',
            'offset',
            'sort_mode',
            'find',
];

$commentslib = TikiLib::lib('comments');
if (isset($_REQUEST['lock']) && isset($_REQUEST['forumId'])) {
    if ($_REQUEST['lock'] == 'y' && $access->checkCsrf()) {
        $commentslib->lock_object_thread('forum:' . ((int)$_REQUEST['forumId']));
    } elseif ($_REQUEST['lock'] == 'n' && $access->checkCsrf()) {
        $commentslib->unlock_object_thread('forum:' . ((int)$_REQUEST['forumId']));
    }
}
if ($prefs['feature_multilingual'] === 'y') {
    $languages = [];
    $langLib = TikiLib::lib('language');
    $languages = $langLib->list_languages();
    $smarty->assign_by_ref('languages', $languages);
} else {
    $_REQUEST["forumLanguage"] = '';
}

if (isset($_REQUEST["save"]) && $access->checkCsrf()) {
    $_REQUEST['useMail'] = isset($_REQUEST['useMail']) ? 'y' : 'n';
    $useMail = $_REQUEST['useMail'];
    $_REQUEST['usePruneUnreplied'] = isset($_REQUEST['usePruneUnreplied']) ? 'y' : 'n';
    $usePruneUnreplied = $_REQUEST['usePruneUnreplied'];
    $_REQUEST['controlFlood'] = isset($_REQUEST['controlFlood']) ? 'y' : 'n';
    $controlFlood = $_REQUEST['controlFlood'];
    $_REQUEST['usePruneOld'] = isset($_REQUEST['usePruneOld']) ? 'y' : 'n';
    $usePruneOld = $_REQUEST['usePruneOld'];
    $_REQUEST['vote_threads'] = isset($_REQUEST['vote_threads']) ? 'y' : 'n';
    $_REQUEST['outbound_mails_for_inbound_mails'] = isset($_REQUEST['outbound_mails_for_inbound_mails']) ? 'y' : 'n';
    $_REQUEST['outbound_mails_reply_link'] = isset($_REQUEST['outbound_mails_reply_link']) ? 'y' : 'n';
    $_REQUEST['topics_list_reads'] = isset($_REQUEST['topics_list_reads']) ? 'y' : 'n';
    $_REQUEST['topics_list_replies'] = isset($_REQUEST['topics_list_replies']) ? 'y' : 'n';
    $_REQUEST['show_description'] = isset($_REQUEST['show_description']) ? 'y' : 'n';
    $_REQUEST['is_flat'] = isset($_REQUEST['is_flat']) ? 'y' : 'n';
    $_REQUEST['topic_summary'] = isset($_REQUEST['topic_summary']) ? 'y' : 'n';
    $_REQUEST['topic_smileys'] = isset($_REQUEST['topic_smileys']) ? 'y' : 'n';
    $_REQUEST['ui_avatar'] = isset($_REQUEST['ui_avatar']) ? 'y' : 'n';
    $_REQUEST['ui_rating_choice_topic'] = isset($_REQUEST['ui_rating_choice_topic']) ? 'y' : 'n';
    $_REQUEST['ui_flag'] = isset($_REQUEST['ui_flag']) ? 'y' : 'n';
    $_REQUEST['ui_email'] = isset($_REQUEST['ui_email']) ? 'y' : 'n';
    $_REQUEST['ui_posts'] = isset($_REQUEST['ui_posts']) ? 'y' : 'n';
    $_REQUEST['ui_level'] = isset($_REQUEST['ui_level']) ? 'y' : 'n';
    $_REQUEST['ui_online'] = isset($_REQUEST['ui_online']) ? 'y' : 'n';
    $_REQUEST['topics_list_pts'] = isset($_REQUEST['topics_list_pts']) ? 'y' : 'n';
    $_REQUEST['topics_list_lastpost'] = isset($_REQUEST['topics_list_lastpost']) ? 'y' : 'n';
    $_REQUEST['topics_list_lastpost_title'] = isset($_REQUEST['topics_list_lastpost_title']) ? 'y' : 'n';
    $_REQUEST['topics_list_lastpost_avatar'] = isset($_REQUEST['topics_list_lastpost_avatar']) ? 'y' : 'n';
    $_REQUEST['topics_list_author'] = isset($_REQUEST['topics_list_author']) ? 'y' : 'n';
    $_REQUEST['topics_list_author_avatar'] = isset($_REQUEST['topics_list_author_avatar']) ? 'y' : 'n';
    $_REQUEST['att_list_nb'] = isset($_REQUEST['att_list_nb']) ? 'y' : 'n';
    if (empty($_REQUEST['threadOrdering'])) {
        $_REQUEST['threadOrdering'] = '';
    }
    if (empty($_REQUEST['threadStyle'])) {
        $_REQUEST['threadStyle'] = '';
    }
    if (empty($_REQUEST['commentsPerPage'])) {
        $_REQUEST['commentsPerPage'] = '';
    }
    if (empty($_REQUEST['image'])) {
        $_REQUEST['image'] = '';
    }
    if (isset($_REQUEST["section"]) && $_REQUEST["section"] == '__new__') {
        $_REQUEST["section"] = $_REQUEST["new_section"];
    }
    // Check for last character being a / or a \
    if (substr($_REQUEST["att_store_dir"], -1) != "\\" && substr($_REQUEST["att_store_dir"], -1) != "/" && $_REQUEST["att_store_dir"] != "") {
        $_REQUEST["att_store_dir"] .= "/";
    }

    $_REQUEST['forumLanguage'] = htmlspecialchars(isset($_REQUEST["forumLanguage"]) ? $_REQUEST["forumLanguage"] : '');

    $tx = TikiDb::get()->begin();
    $fid = $commentslib->replace_forum(
        [
            'forumId' => $_REQUEST["forumId"],
            'name' => $_REQUEST["name"],
            'description' => $_REQUEST["description"] ?? "",
            'controlFlood' => $controlFlood ?? 'n',
            'floodInterval' => $_REQUEST["floodInterval"] ?? 120,
            'moderator' => $_REQUEST["moderator"] ?? 'admin',
            'mail' => $_REQUEST["mail"] ?? '',
            'useMail' => $useMail ?? 'n',
            'usePruneUnreplied' => $usePruneUnreplied ?? 'n',
            'pruneUnrepliedAge' => $_REQUEST["pruneUnrepliedAge"] ?? 2592000,
            'usePruneOld' => $usePruneOld ?? 'n',
            'pruneMaxAge' => $_REQUEST["pruneMaxAge"] ?? 259200,
            'topicsPerPage' => $_REQUEST["topicsPerPage"] ?? 10,
            'topicOrdering' => $_REQUEST["topicOrdering"] ?? 'lastPost_desc',
            'threadOrdering' => $_REQUEST["threadOrdering"] ?? '',
            'section' => $_REQUEST["section"] ?? '',
            'topics_list_reads' => $_REQUEST['topics_list_reads'] ?? 'y',
            'topics_list_replies' => $_REQUEST['topics_list_replies'] ?? 'y',
            'topics_list_pts' => $_REQUEST['topics_list_pts'] ?? 'n',
            'topics_list_lastpost' => $_REQUEST['topics_list_lastpost'] ?? 'y',
            'topics_list_author' => $_REQUEST['topics_list_author'] ?? 'y',
            'vote_threads' => $_REQUEST['vote_threads'] ?? 'n',
            'show_description' => $_REQUEST['show_description'] ?? 'n',
            'inbound_pop_server' => $_REQUEST['inbound_pop_server'] ?? '',
            'inbound_pop_port' => $_REQUEST['inbound_pop_port'] ?? 110,
            'inbound_pop_user' => $_REQUEST['inbound_pop_user'] ?? '',
            'inbound_pop_password' => $_REQUEST['inbound_pop_password'] ?? '',
            'outbound_address' => trim($_REQUEST['outbound_address']) ?? '',
            'outbound_mails_for_inbound_mails' => $_REQUEST['outbound_mails_for_inbound_mails'] ?? '',
            'outbound_mails_reply_link' => $_REQUEST['outbound_mails_reply_link'] ?? 'n',
            'outbound_from' => $_REQUEST['outbound_from'] ?? '',
            'topic_smileys' => $_REQUEST['topic_smileys'] ?? 'n',
            'topic_summary' => $_REQUEST['topic_summary'] ?? 'n',
            'ui_avatar' => $_REQUEST['ui_avatar'] ?? 'y',
            'ui_rating_choice_topic' => $_REQUEST['ui_rating_choice_topic'] ?? 'y',
            'ui_flag' => $_REQUEST['ui_flag'] ?? 'y',
            'ui_posts' => $_REQUEST['ui_posts'] ?? 'n',
            'ui_level' => $_REQUEST['ui_level'] ?? 'n',
            'ui_email' => $_REQUEST['ui_email'] ?? 'n',
            'ui_online' => $_REQUEST['ui_online'] ?? 'n',
            'approval_type' => $_REQUEST['approval_type'] ?? 'all_posted',
            'moderator_group' => $_REQUEST['moderator_group'] ?? '',
            'forum_password' => $_REQUEST['forum_password'] ?? '',
            'forum_use_password' => $_REQUEST['forum_use_password'] ?? 'n',
            'att' => $_REQUEST['att'] ?? 'att_no',
            'att_store' => $_REQUEST['att_store'] ?? 'db',
            'att_store_dir' => $_REQUEST['att_store_dir'] ?? '',
            'att_max_size' => $_REQUEST['att_max_size'] ?? 1000000,
            'forum_last_n' => $_REQUEST['forum_last_n'] ?? 0,
            'commentsPerPage' => $_REQUEST['commentsPerPage'] ?? '',
            'threadStyle' => $_REQUEST['threadStyle'] ?? '',
            'is_flat' => $_REQUEST['is_flat'] ?? 'n',
            'att_list_nb' => $_REQUEST['att_list_nb'] ?? 'n',
            'topics_list_lastpost_title' => $_REQUEST['topics_list_lastpost_title'] ?? 'y',
            'topics_list_lastpost_avatar' => $_REQUEST['topics_list_lastpost_avatar'] ?? 'n',
            'topics_list_author_avatar' => $_REQUEST['topics_list_author_avatar'] ?? 'n',
            'forumLanguage' => $_REQUEST['forumLanguage'] ?? '',
            'parentId' => $_REQUEST["parentId"] ?? 0
        ]
    );

    if ($fid) {
        Feedback::success(tr('Forum saved'));
    } else {
        Feedback::error(tr('Forum not saved'));
    }

    $attributelib = TikiLib::lib('attribute');
    $attributelib->set_attribute('forum', $fid, 'tiki.object.image', (int) $_REQUEST['image']);

    $cat_type = 'forum';
    $cat_objid = $fid;
    $cat_desc = substr($_REQUEST["description"], 0, 200);
    $cat_name = $_REQUEST["name"];
    $cat_href = "tiki-view_forum.php?forumId=" . $cat_objid;
    include_once("categorize.php");
    $_REQUEST["forumId"] = $fid;

    $tx->commit();

    $cookietab = 1;
}
if (
    ! empty($_REQUEST['duplicate']) && ! empty($_REQUEST['duplicate_name']) && ! empty($_REQUEST['duplicate_forumId'])
    && $access->checkCsrf()
) {
    $newForumId = $commentslib->duplicate_forum(
        $_REQUEST['duplicate_forumId'],
        $_REQUEST['duplicate_name'],
        isset($_REQUEST['description']) ? $_REQUEST['description'] : ''
    );
    if ($newForumId) {
        Feedback::success(tr('Forum duplicated'));
    } else {
        Feedback::error(tr('Forum not duplicated'));
    }
    if (isset($_REQUEST['dupCateg']) && $_REQUEST['dupCateg'] == 'on' && $prefs['feature_categories'] == 'y') {
        $categlib = TikiLib::lib('categ');
        $cats = $categlib->get_object_categories('forum', $_REQUEST['forumId']);
        $catObjectId = $categlib->add_categorized_object('forum', $newForumId, isset($_REQUEST['description']) ? $_REQUEST['description'] : '', $_REQUEST['name'], "tiki-view_forum.php?forumId=$newForumId");
        foreach ($cats as $cat) {
            $categlib->categorize($catObjectId, $cat);
        }
    }
    if (isset($_REQUEST['dupPerms']) && $_REQUEST['dupPerms'] == 'on') {
        $userlib = TikiLib::lib('user');
        $userlib->copy_object_permissions($_REQUEST['forumId'], $newForumId, 'forum');
    }
    $_REQUEST['forumId'] = $newForumId;
}
if ($_REQUEST["forumId"]) {
    $info = $commentslib->get_forum($_REQUEST["forumId"]);

    $attributelib = TikiLib::lib('attribute');
    $attributes = $attributelib->get_attributes('forum', $_REQUEST['forumId']);
    $info['image'] = isset($attributes['tiki.object.image']) ? $attributes['tiki.object.image'] : '';
} else {
    $info = [];
    $info["name"] = '';
    $info["description"] = '';
    $info["controlFlood"] = 'n';
    $info["floodInterval"] = 120;
    $info["moderator"] = 'admin';
    $info["section"] = '';
    $info["mail"] = '';
    $info["topicsPerPage"] = 10;
    $info["useMail"] = 'n';
    $info["topicOrdering"] = 'lastPost_desc';
    $info["threadOrdering"] = '';
    $info["threadStyle"] = '';
    $info["commentsPerPage"] = '';
    $info["usePruneUnreplied"] = 'n';
    $info["pruneUnrepliedAge"] = 60 * 60 * 24 * 30;
    $info["usePruneOld"] = 'n';
    $info["pruneMaxAge"] = 60 * 60 * 24 * 30;
    $info["topics_list_replies"] = 'y';
    $info["show_description"] = 'n';
    $info["outbound_address"] = '';
    $info["outbound_mails_for_inbound_mails"] = 'n';
    $info["outbound_mails_reply_link"] = 'n';
    $info["outbound_from"] = '';
    $info["inbound_pop_server"] = '';
    $info["inbound_pop_port"] = 110;
    $info["inbound_pop_user"] = '';
    $info["inbound_pop_password"] = '';
    $info["topic_summary"] = 'n';
    $info["topic_smileys"] = 'n';
    $info["ui_avatar"] = 'y';
    $info["ui_rating_choice_topic"] = 'n';
    $info["ui_flag"] = 'y';
    $info["ui_posts"] = 'n';
    $info['ui_level'] = 'n';
    $info["ui_email"] = 'n';
    $info["ui_online"] = 'n';
    $info["approval_type"] = 'all_posted';
    $info["moderator_group"] = '';
    $info['forum_password'] = '';
    $info['forum_use_password'] = 'n';
    $info['att'] = 'att_no';
    $info['att_store'] = 'db';
    $info['att_store_dir'] = '';
    $info['att_max_size'] = 1000000;
    $info['att_list_nb'] = 'n';
    $info["topics_list_reads"] = 'y';
    $info["topics_list_pts"] = 'n';
    $info["topics_list_lastpost"] = 'y';
    $info['topics_list_lastpost_title'] = 'y';
    $info['topics_list_lastpost_avatar'] = 'n';
    $info["topics_list_author"] = 'y';
    $info['topics_list_author_avatar'] = 'n';
    $info["vote_threads"] = 'n';
    $info["forum_last_n"] = 0;
    $info["is_flat"] = 'n';
    $info["forumLanguage"] = '';
    $info['image'] = '';
}
$smarty->assign('forumId', $_REQUEST["forumId"]);
$smarty->assign('parentId', $_REQUEST["parentId"]);
foreach ($info as $key => $value) {
    if ($key == "section") {
        // conflict with section management
        $smarty->assign("forumSection", $value);
    } else {
        $smarty->assign($key, $value);
    }
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = $prefs['forums_ordering'];
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
if (! isset($_REQUEST["offset"])) {
    $offset = 0;
} else {
    $offset = $_REQUEST["offset"];
}
$smarty->assign_by_ref('offset', $offset);
if (isset($_REQUEST["find"])) {
    $find = $_REQUEST["find"];
} else {
    $find = '';
}
$smarty->assign('find', $find);
$smarty->assign_by_ref('sort_mode', $sort_mode);
if (isset($_REQUEST['numrows'])) {
    $maxRecords = $_REQUEST['numrows'];
} else {
    $maxRecords = $prefs['maxRecords'];
}
$channels = $commentslib->list_forums($offset, $maxRecords, $sort_mode, $find, $_REQUEST['parentId']);
if ($_REQUEST['parentId'] > 0) {
    $forumParent = $commentslib->get_forum($_REQUEST['parentId']);
    $smarty->assign('parent', $forumParent);
}
$max = count($channels["data"]);
for ($i = 0; $i < $max; $i++) {
    if ($userlib->object_has_one_permission($channels["data"][$i]["forumId"], 'forum')) {
        $channels["data"][$i]["individual"] = 'y';
        if ($tiki_p_admin == 'y' || $userlib->object_has_permission($user, $channels["data"][$i]["forumId"], 'forum', 'tiki_p_admin_forum')) {
            $channels["data"][$i]["individual_tiki_p_admin_forum"] = 'y';
        }
    } elseif ($userlib->user_has_perm_on_object($user, $channels["data"][$i]["forumId"], 'forum', 'tiki_p_admin_forum')) {
        $channels["data"][$i]["individual_tiki_p_admin_forum"] = 'y';
    } else {
        $channels["data"][$i]["individual"] = 'n';
    }
}
$smarty->assign_by_ref('channels', $channels["data"]);
$smarty->assign_by_ref('cant', $channels["cant"]);
$cat_type = 'forum';
$cat_objid = $_REQUEST["forumId"];
$categories = [];
include_once("categorize_list.php");
if (! empty($_REQUEST['dup_mode'])) {
    if ($offset == 0 && ($maxRecords == - 1 || $channels['cant'] <= $maxRecords)) {
        $smarty->assign_by_ref('allForums', $channels['data']);
    } else {
        $allForums = $commentslib->list_forums(0, -1, 'name_asc');
        $smarty->assign_by_ref('allForums', $allForums['data']);
    }
    $smarty->assign_by_ref('dup_mode', $_REQUEST['dup_mode']);
    $cookitab = 2;
}
$users = $userlib->list_all_users();
$smarty->assign_by_ref('users', $users);
$groups = $userlib->list_all_groups();
$smarty->assign_by_ref('groups', $groups);
$maxAttachSize = ini_get('upload_max_filesize');
if (preg_match('/^([\d\.]+)([gmk])?$/i', $maxAttachSize, $matches) && ! empty($matches[2])) {
    $maxAttachSize = $matches[1];
    switch (strtolower($matches[0][strlen($matches[0]) - 1])) {
        case 'g':
            $maxAttachSize *= 1024;
            // no break
        case 'm':
            $maxAttachSize *= 1024;
            // no break
        case 'k':
            $maxAttachSize *= 1024;
    }
}

//add tablesorter sorting and filtering
$ts = Table_Check::setVars('adminforums', true);
if ($ts['enabled'] && ! $ts['ajax']) {
    //set tablesorter code
    Table_Factory::build(
        'TikiAdminForums',
        [
            'id' => $ts['tableid'],
            'total' => $channels['cant'],
        ]
    );
}

$smarty->assign_by_ref('maxAttachSize', $maxAttachSize);

$oneday = 60 * 60 * 24;

$prune_values = [
    1 * $oneday => '1' . ' ' . tra('day'),
    2 * $oneday => '2' . ' ' . tra('days'),
    5 * $oneday => '5' . ' ' . tra('days'),
    7 * $oneday => '7' . ' ' . tra('days'),
    15 * $oneday => '15' . ' ' . tra('days'),
    30 * $oneday => '30' . ' ' . tra('days'),
    60 * $oneday => '60' . ' ' . tra('days'),
    90 * $oneday => '90' . ' ' . tra('days'),
];

$smarty->assign('pruneUnrepliedAge_options', $prune_values);
$smarty->assign('pruneMaxAge_options', $prune_values);

$flood_values = [
    15 => '15' . ' ' . tra('secs'),
    30 => '30' . ' ' . tra('secs'),
    60 => '1' . ' ' . tra('min'),
    120 => '2' . ' ' . tra('mins'),
];

$smarty->assign(
    'flood_options',
    [
        15 => '15' . ' ' . tra('secs'),
        30 => '30' . ' ' . tra('secs'),
        60 => '1' . ' ' . tra('min'),
        120 => '2' . ' ' . tra('mins')
    ]
);

$smarty->assign(
    'approval_options',
    [
        'all_posted' => tra('All posted'),
        'queue_anon' => tra('Queue anonymous posts'),
        'queue_all' => tra('Queue all posts')
    ]
);

$smarty->assign(
    'attachment_options',
    [
        'att_no' => tra('No attachments'),
        'att_all' => tra('Everybody can attach'),
        'att_perm' => tra('Only users with attach permission'),
        'att_admin' => tra('Moderators and admin can attach')
    ]
);

$smarty->assign(
    'forum_use_password_options',
    [
        'n' => tra('No'),
        't' => tra('Topics only'),
        'a' => tra('All posts')
    ]
);

$smarty->assign(
    'forum_last_n_options',
    [
        0 => tra('No display'),
        5 => '5',
        10 => '10',
        20 => '20'
    ]
);

$smarty->assign(
    'topicOrdering_options',
    [
        'commentDate_desc' => tra('Date (desc)'),
        'commentDate_asc' => tra('Date (asc)'),
        'average_desc' => tra('Score (desc)'),
        'replies_desc' => tra('Replies (desc)'),
        'hits_desc' => tra('Reads (desc)'),
        'lastPost_desc' => tra('Latest post (desc)'),
        'title_desc' => tra('Title (desc)'),
        'title_asc' => tra('Title (asc)')
    ]
);

$smarty->assign(
    'threadOrdering_options',
    [
        '' => tra('Default'),
        'commentDate_desc' => tra('Newest first'),
        'commentDate_asc' => tra('Oldest first'),
        'points_desc' => tra('Score'),
        'title_desc' => tra('Title (desc)'),
        'title_asc' => tra('Title (asc)')
    ]
);

$smarty->assign(
    'threadStyle_options',
    [
        '' => tra('Default'),
        'commentStyle_plain' => tra('Plain'),
        'commentStyle_threaded' => tra('Threaded'),
        'commentStyle_headers' => tra('Headers only')
    ]
);

$smarty->assign(
    'commentsPerPage_options',
    [
        '' => tra('Default'),
        10 => '10',
        20 => '20',
        30 => '30',
        999999 => tra('All')
    ]
);

$sections = $tikilib->get_forum_sections();
$smarty->assign_by_ref('sections', $sections);
include_once('tiki-section_options.php');
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
if ($ts['ajax']) {
    $smarty->display('tiki-admin_forums.tpl');
} else {
    $smarty->assign('mid', 'tiki-admin_forums.tpl');
    $smarty->display("tiki.tpl");
}
