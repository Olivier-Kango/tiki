<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/** \brief send the email notifications dealing with the forum changes to
 * \brief outbound address + admin notification addresses / forum admin email + watching users addresses
 * \param $event = 'forum_post_topic' or 'forum_post_thread' or 'forum_post_queued'
 * \param $object = forumId watch if forum_post_topic (and forum_post_queued) or topicId watch if forum_post_thread
 * \param $threadId = topicId if forum_post_thread
 * \param $title of the message
 * \param $topicName name of the parent topic
 */

function sendForumEmailNotification(
    $event,
    $object,
    $forum_info,
    $title,
    $data,
    $author,
    $topicName,
    $messageId,
    $inReplyTo,
    $threadId,
    $parentId,
    $contributions = '',
    $queueId = 0
) {

    global $prefs;
    $userlib = TikiLib::lib('user');
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');

    // Per-forum From address overrides global default.
    if ($forum_info['outbound_from'] && ($event == 'forum_post_thread' || $event == 'forum_post_topic')) {
        $author = $userlib->clean_user($author);
        $my_sender = $forum_info['outbound_from'];
    } else {
        $my_sender = $prefs['sender_email'];
    }

    //outbound email ->  will be sent in utf8 - from sender_email
    if ($forum_info['outbound_address'] && ($event == 'forum_post_thread' || $event == 'forum_post_topic')) {
        include_once(__DIR__ . '/../webmail/tikimaillib.php');
        $mail = new TikiMail();
        $mail->setSubject($title);
        if (! empty($forum_info['outbound_mails_reply_link']) && $forum_info['outbound_mails_reply_link'] == 'y') {
            $foo = parse_url($_SERVER["REQUEST_URI"]);
            $machine = $tikilib->httpPrefix(true) . dirname($foo["path"]);
            if ($event == 'forum_post_topic') {
                $reply_link = "$machine/tiki-view_forum_thread.php?comments_parentId=$threadId#form";
            } else {
                $reply_link = "$machine/tiki-view_forum_thread.php?comments_reply_threadId=$threadId&comments_parentId=$threadId&post_reply=1#form";
            }
        } else {
            $reply_link = '';
        }

        // optionally strip wiki markup from the outgoing mail
        if ($prefs['feature_forum_parse'] === 'y' && $prefs['forum_strip_wiki_syntax_outgoing'] === 'y') {
            $data = strip_tags(TikiLib::lib('parser')->parse_data($data, [
                'noparseplugins' => true,
                'absolute_links' => true,
            ]));
        }

        $smarty->assign('title', $title);
        $smarty->assign('data', $data);
        $smarty->assign('reply_link', $reply_link);
        $smarty->assign('author', $author);
        $mail_data = $smarty->fetch("mail/forum_outbound.tpl");
        $mail->setText($mail_data);
        $mail->setReplyTo($my_sender, $author);
        $mail->setFrom($my_sender, $author);
        $mail->setSubject($topicName);

        $commentslib = TikiLib::lib('comments');
        $attachments = $commentslib->get_thread_attachments($event == 'forum_post_topic' ? $threadId : $object, 0);

        if (count($attachments) > 0) {
            foreach ($attachments as $att) {
                $att_data = $commentslib->get_thread_attachment($att['attId']);
                if ($att_data['dir'] . $att_data['path'] == "") { // no path to file on disk
                    $file = $att_data['data']; // read file from database
                } else {
                    $file = file_get_contents($att_data['dir'] . $att_data['path']); // read file from disk
                }
                $mail->addAttachment($file, $att_data['filename'], $att_data['filetype']);
            }
        }

        // Message-ID is set below buildMessage because otherwise lib/webmail/htmlMimeMail.php will over-write it.
        $additionalHeaders = \Tiki\Notifications\Email::getEmailThreadHeaders('forum', $threadId);
        foreach ($additionalHeaders as $headerName => $headerValue) {
            $mail->setHeader($headerName, $headerValue);
        }

        $mail->send([$forum_info['outbound_address']]);
    }

    $nots = [];
    $defaultLanguage = $prefs['site_language'];

    // Users watching this forum or this post
    if ($prefs['feature_user_watches'] == 'y' || $prefs['feature_group_watches'] == 'y') {
        $nots_raw = $tikilib->get_event_watches($event, $event == 'forum_post_topic' ? $forum_info['forumId'] : $parentId, $forum_info);
        $nots = [];
        $users = [];
        foreach ($nots_raw as $n) {
            if (
                $n['user'] != $author
                    && ! in_array($n['user'], $users)
            ) {
                // make sure user receive only one notification even if he is monitoring both the topic and thread
                $n['language'] = $tikilib->get_user_preference($n['user'], "language", $defaultLanguage);
                $nots[] = $n;
                $users[] = $n['user'];
            }
        }
    }

    // Moderation email
    if ($event == 'forum_post_queued') {
        $nots = [];
        if (! empty($forum_info['moderator'])) {
            $not['email'] = $userlib->get_user_email($forum_info['moderator']);
            $not['user'] = $forum_info['moderator'];
            $not['language'] = $tikilib->get_user_preference($forum_info['moderator'], "language", $defaultLanguage);
            $nots[] = $not;
        }
        if (! empty($forum_info['moderator_group'])) {
            $moderators = $userlib->get_members($forum_info['moderator_group']);
            foreach ($moderators as $mod) {
                if ($mod != $nots[0]['user']) { // avoid duplication
                    $not['email'] = $userlib->get_user_email($mod);
                    $not['user'] = $mod;
                    $not['language'] = $tikilib->get_user_preference($mod, "language", $defaultLanguage);
                    $nots[] = $not;
                }
            }
        }
    }

    // Special forward address
    //TODO: merge or use the admin notification feature
    if ($forum_info["useMail"] == 'y') {
        $not['email'] = $forum_info['mail'];
        if ($not['user'] = $userlib->get_user_by_email($forum_info['mail'])) {
            $not['language'] = $tikilib->get_user_preference($not['user'], "language", $defaultLanguage);
        } else {
            $not['language'] = $defaultLanguage;
        }
        $nots[] = $not;
    }

    if ($prefs['feature_user_watches'] == 'y' && $prefs['feature_daily_report_watches'] == 'y') {
        $reportsManager = Reports_Factory::build('Reports_Manager');
        $reportsManager->addToCache(
            $nots,
            [
                "event" => $event,
                "forumId" => $forum_info['forumId'],
                "forumName" => $forum_info['name'],
                "topicId" => $parentId,
                "threadId" => $threadId,
                "threadName" => $topicName,
                "user" => $author
            ]
        );
    }

    if (count($nots)) {
        include_once(__DIR__ . '/../webmail/tikimaillib.php');
        $smarty->assign('mail_forum', $forum_info["name"]);
        $smarty->assign('mail_title', $title);
        $smarty->assign('mail_date', $tikilib->now);
        $smarty->assign('mail_message', $data);
        $smarty->assign('mail_author', $author);
        if ($prefs['feature_contribution'] == 'y' && ! empty($contributions)) {
            $contributionlib = TikiLib::lib('contribution');
            $smarty->assign('mail_contributions', $contributionlib->print_contributions($contributions));
        }
        $smarty->assign('forumId', $forum_info["forumId"]);
        if ($event == "forum_post_topic") {
            $smarty->assign('new_topic', 'y');
            $smarty->assign('threadId', $threadId);
        } else {
            $smarty->assign('new_topic', 'n');
            $smarty->assign('threadId', $threadId);
        }
        if ($parentId) {
            $smarty->assign('topicId', $parentId);
        } else {
            $smarty->assign('topicId', $threadId);
        }
        $smarty->assign('mail_topic', $topicName);
        foreach ($nots as $not) {
            $mail = new TikiMail();
            $mail->setUser($not['user']);
            if ($event == 'forum_post_queued') {
                if ($prefs['forum_moderator_email_approve'] == 'y') {
                    $smarty->assign('queueId', $queueId);
                    $smarty->assign('approvalhash', md5($queueId . $title . $data . $author));
                }
                $mail_data = $smarty->fetchLang($not['language'], "mail/user_watch_forum_queued_subject.tpl");
            } else {
                $mail_data = $smarty->fetchLang($not['language'], "mail/user_watch_forum_subject.tpl");
            }
            $mail->setSubject($mail_data);
            if ($event == 'forum_post_queued') {
                $mail_data = $smarty->fetchLang($not['language'], "mail/forum_post_queued_notification.tpl");
            } else {
                $mail_data = $smarty->fetchLang($not['language'], "mail/forum_post_notification.tpl");
            }
            $mail->setText($mail_data);
            $additionalHeaders = \Tiki\Notifications\Email::getEmailThreadHeaders('forum', $threadId);
            foreach ($additionalHeaders as $headerName => $headerValue) {
                $mail->setHeader($headerName, $headerValue);
            }
            $mail->send([$not['email']]);
        }
    }
}

/**
 * \brief test if email already in the notification list
 */
function testEmailInList($nots, $email)
{
    foreach (array_keys($nots) as $i) {
        if ($nots[$i]['email'] == $email) {
            return true;
        }
    }
    return false;
}

/**
 *\brief send the email notifications dealing with wiki page  changes to
 * admin notification addresses + watching users addresses (except editor is configured)
 * \$event: 'wiki_page_created'|'wiki_page_changed'|wiki_page_deleted |wiki_file_attached
 *
 */
function sendWikiEmailNotification(
    $wikiEvent,
    $pageName,
    $edit_user,
    $edit_comment,
    $oldver,
    $edit_data,
    $machine = '',
    $diff = '',
    $minor = false,
    $contributions = '',
    $structure_parent_id = 0,
    $attId = 0,
    $lang = ''
) {

    global $prefs;
    $userlib = TikiLib::lib('user');
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');
    $notificationlib = TikiLib::lib('notification');
    $nots = [];
    $defaultLanguage = $prefs['site_language'];
    if ($wikiEvent == 'wiki_file_attached') {
        $event = 'wiki_page_changed';
    } else {
        $event = $wikiEvent;
    }

    if ($prefs['feature_user_watches'] == 'y') {
        $nots = $tikilib->get_event_watches($event, $pageName);
    }

    if ($prefs['feature_user_watches'] == 'y' && $event == 'wiki_page_changed') {
        $structlib = TikiLib::lib('struct');
        $nots2 = $structlib->get_watches($pageName);
        if (! empty($nots2)) {
            $nots = array_merge($nots, $nots2);
        }

        if ($prefs['wiki_watch_editor'] != "y" || $prefs['user_wiki_watch_editor'] != "y") {
            for ($i = count($nots) - 1; $i >= 0; --$i) {
                if ($nots[$i]['user'] == $edit_user) {
                    unset($nots[$i]);
                    break;
                }
            }
        }

        foreach (array_keys($nots) as $i) {
            $nots[$i]['language'] = $tikilib->get_user_preference($nots[$i]['user'], "language", $defaultLanguage);
        }
    }

    if ($prefs['feature_user_watches'] == 'y' && $event == 'wiki_page_created' && $structure_parent_id) {
        $structlib = TikiLib::lib('struct');
        $nots = array_merge($nots, $structlib->get_watches('', $structure_parent_id));
    }

    // admin notifications
    // If it's a minor change, get only the minor change watches.
    if ($minor) {
        $emails = $notificationlib->get_mail_events('wiki_page_changes_incl_minor', $pageName); // look for pageName and any page
    } else { // else if it's not minor change, get both watch types.
        $emails1 = $notificationlib->get_mail_events('wiki_page_changes', $pageName); // look for pageName and any page
        $emails2 = $notificationlib->get_mail_events('wiki_page_changes_incl_minor', $pageName); // look for pageName and any page
        $emails = array_merge($emails1, $emails2);
    }
    foreach ($emails as $email) {
        if (($prefs['wiki_watch_editor'] != "y" || $prefs['user_wiki_watch_editor'] != "y") && $email == $edit_user) {
            continue;
        }
        if (! testEmailInList($nots, $email)) {
            $not = ['email' => $email];
            if ($not['user'] = $userlib->get_user_by_email($email)) {
                $not['language'] = $tikilib->get_user_preference($not['user'], "language", $defaultLanguage);
            } else {
                $not['language'] = $defaultLanguage;
            }
            $nots[] = $not;
        }
    }

    if ($edit_user == '') {
        $edit_user = tra('Anonymous');
    }

    if ($prefs['feature_user_watches'] == 'y' && $prefs['feature_daily_report_watches'] == 'y') {
        if ($wikiEvent == 'wiki_file_attached') {
            $reportsManager = Reports_Factory::build('Reports_Manager');
            $reportsManager->addToCache(
                $nots,
                [
                    "event" => $wikiEvent,
                    "pageName" => $pageName,
                    'attId' => $attId,
                    "editUser" => $edit_user,
                    "editComment" => $edit_comment,
                    'filename' => $edit_data
                ]
            );
        } else {
            $reportsManager = Reports_Factory::build('Reports_Manager');
            $reportsManager->addToCache(
                $nots,
                [
                    "event" => $wikiEvent,
                    "pageName" => $pageName,
                    "object" => $pageName,
                    "editUser" => $edit_user,
                    "editComment" => $edit_comment,
                    "oldVer" => $oldver
                ]
            );
        }
    }

    if (count($nots)) {
        $edit_data = TikiLib::htmldecode($edit_data);
        include_once(__DIR__ . '/../mail/maillib.php');
        $smarty->assign('mail_site', $_SERVER["SERVER_NAME"]);
        $smarty->assign('mail_page', $pageName);
        $smarty->assign('mail_date', $tikilib->now);
        $smarty->assign('mail_user', $edit_user);
        $smarty->assign('mail_comment', $edit_comment);
        $newver = (int) $oldver + 1;
        $smarty->assign('mail_oldver', $oldver);
        $smarty->assign('mail_newver', $newver);
        $smarty->assign('mail_data', $edit_data);
        $smarty->assign('mail_attId', $attId);

        if ($prefs['feature_contribution'] == 'y' && ! empty($contributions)) {
            $contributionlib = TikiLib::lib('contribution');
            $smarty->assign('mail_contributions', $contributionlib->print_contributions($contributions));
        }

        $smarty->assign_by_ref('mail_pagedata', $edit_data);
        $smarty->assign_by_ref('mail_diffdata', $diff);

        if ($event == 'wiki_page_created') {
            $smarty->assign('mail_action', 'new');
        } elseif ($event == 'wiki_page_deleted') {
            $smarty->assign('mail_action', 'delete');
        } elseif ($wikiEvent == 'wiki_file_attached') {
            $smarty->assign('mail_action', 'attach');
        } else {
            $smarty->assign('mail_action', 'edit');
        }

        include_once(__DIR__ . '/../webmail/tikimaillib.php');

        foreach ($nots as $not) {
            if (empty($not['email'])) {
                continue;
            }
            $smarty->assign('watchId', isset($not['watchId']) ? $not['watchId'] : '');

            $mail_subject = $smarty->fetchLang($not['language'], "mail/user_watch_wiki_page_changed_subject.tpl");
            $mail_data = $smarty->fetchLang($not['language'], "mail/user_watch_wiki_page_changed.tpl");

            $mail = new TikiMail($not['user']);
            $mail->setSubject(sprintf($mail_subject, $pageName));
            $mail->setText($mail_data);
            if (! $mail->send([$not['email']]) && Perms::get()->admin) {
                Feedback::error(['mes' => $mail->errors]);
            }
        }
    }
}

/**
 *\brief Send email notification to a list of emails or a list of (email, user) in a charset+language associated with each email
 * \param $watches : bidimensional array of watches. Each watch has user, language, email and watchId keys.
 * \param $dummy: unused
 * \param $subjectTpl: subject template file or null (ex: "submission_notifcation.tpl")
 * \param $subjectParam: le param to be inserted in the subject or null
 * \param $txtTpl : texte template file (ex: "submission_notifcation.tpl")
 * \param $from email from to not the default one
 * \param $fromName name to use when sending emails
 * \param $additionalHeaders additional headers to append to email
 * \ $smarty is supposed to be already built to fit $txtTpl
 * \return the nb of sent emails
 */

function sendEmailNotification($watches, $dummy, $subjectTpl, $subjectParam, $txtTpl, $from = '', $fromName = null, $additionalHeaders = [])
{
    global $prefs;

    $smarty = TikiLib::lib('smarty');
    $tikilib = TikiLib::lib('tiki');

    $userlib = TikiLib::lib('user');
    include_once(__DIR__ . '/../webmail/tikimaillib.php');
    $sent = 0;
    $smarty->assign('mail_date', $tikilib->now);

    foreach ($watches as $watch) {
        $mail = new TikiMail(null, $from, $fromName);

        foreach ($additionalHeaders as $headerName => $headerValue) {
            $mail->setHeader($headerName, $headerValue);
        }

        $smarty->assign('watchId', $watch['watchId'] ?? null);
        if ($watch['user']) {
            $mail->setUser($watch['user']);
        }
        if ($subjectTpl) {
            $mail_data = getMailDataFromWikiPage($subjectTpl, $watch['language']);

            if (! $mail_data) {
                $mail_data = $smarty->fetchLang($watch['language'], "mail/" . $subjectTpl);
            }
            if ($subjectParam) {
                $mail_data = sprintf($mail_data, $subjectParam);
            }
            $mail_data = preg_replace('/%[sd]/', '', $mail_data);// partial cleaning if param not supply and %s in text
            $mail->setSubject($mail_data);
        } else {
            $mail->setSubject($subjectParam);
        }
        $text = getMailDataFromWikiPage($txtTpl);
        if (! $text) {
            $text = $smarty->fetchLang($watch['language'], "mail/" . $txtTpl);
        }
        $mail->setText($text);
        if ($mail->send([$watch['email']])) {
            $sent++;
        }
    }
    return $sent;
}

function getMailDataFromWikiPage($txtTpl, $language = '')
{
    $tikilib = TikiLib::lib('tiki');

    $tplWikipageName = ucwords(preg_replace('/_/', ' ', $txtTpl));
    $tplWikipageName = preg_replace('/\.tpl$/', ' TPL', $tplWikipageName);

    if ($tikilib->page_exists($tplWikipageName)) {
        return TikiLib::lib('smarty')->fetchLang($language, 'tplwiki:' . $tplWikipageName);
    }
    return false;
}

function activeErrorEmailNotivation()
{
    set_error_handler("sendErrorEmailNotification");
}

function sendErrorEmailNotification($errno, $errstr, $errfile = '?', $errline = '?')
{
    $tikilib = TikiLib::lib('tiki');
    if (($errno & error_reporting()) == 0) { /* ignore error */
        return;
    }

    switch ($errno) {
        case E_ERROR:
            $err = 'FATAL';
            break;

        case E_WARNING:
            $err = 'ERROR';
            break;

        case E_NOTICE:
            $err = 'WARNING';
            break;

        default:
            $err = "";
    }

    $email = $tikilib->get_user_email('admin');

    mail(
        $email,
        "PHP: $errfile, $errline",
        "$errfile, Line $errline\n$err($errno)\n$errstr"
    );
}

function sendFileGalleryEmailNotification($event, $galleryId, $galleryName, $name, $filename, $description, $action, $user, $fileId)
{
    global $prefs;
    $userlib = TikiLib::lib('user');
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');

    $nots = [];
    $defaultLanguage = $prefs['site_language'];

    // Users watching this gallery
    if ($prefs['feature_user_watches'] == 'y') {
        $nots = $tikilib->get_event_watches($event, $galleryId);
        for ($i = count($nots) - 1; $i >= 0; --$i) {
            $nots[$i]['language'] = $tikilib->get_user_preference($nots[$i]['user'], "language", $defaultLanguage);
        }

        if ($prefs['feature_daily_report_watches'] == 'y') {
            $reportsManager = Reports_Factory::build('Reports_Manager');
            $reportsManager->addToCache(
                $nots,
                [
                    "event" => $event,
                    "name" => $name,
                    "fileId" => $fileId,
                    "fileName" => $filename,
                    "galleryId" => $galleryId,
                    "galleryName" => $galleryName,
                    "action" => $action,
                    "user" => $user
                ]
            );
        }
    }

    if (count($nots)) {
        include_once(__DIR__ . '/../webmail/tikimaillib.php');
        $smarty->assign('galleryName', $galleryName);
        $smarty->assign('galleryId', $galleryId);
        $smarty->assign('fileId', $fileId);
        $smarty->assign('fname', $name);
        $smarty->assign('filename', $filename);
        $smarty->assign('fdescription', $description);
        $smarty->assign('mail_date', $tikilib->now);
        $smarty->assign('author', $user);
        foreach ($nots as $not) {
            $mail = new TikiMail();
            $mail->setUser($not['user']);
            $mail_data = $smarty->fetchLang($not['language'], "mail/user_watch_file_gallery_changed_subject.tpl");
            $mail->setSubject(sprintf($mail_data, $galleryName));
            if ($action == 'upload file') {
                $mail_data = $smarty->fetchLang($not['language'], "mail/user_watch_file_gallery_upload.tpl");
            } elseif ($action == 'remove file') {
                $mail_data = $smarty->fetchLang($not['language'], "mail/user_watch_file_gallery_remove_file.tpl");
            }
            $mail->setText($mail_data);
            $mail->send([$not['email']]);
        }
    }
}

/**
 * Sends E-Mail notifications for a created/changed/removed category.
 * The Array $values contains a selection of the following items:
 * event, categoryId, categoryName, categoryPath, description, parentId, parentName, action,
 * oldCategoryName, oldCategoryPath, oldDescription, oldParendId, oldParentName,
 * objectName, objectType, objectUrl
 */
function sendCategoryEmailNotification($values)
{
    $event = $values['event'];
    $action = $values['action'];
    $categoryId = $values['categoryId'];
    $categoryName = $values['categoryName'];
    $categoryPath = $values['categoryPath'];
    $description = $values['description'];
    $parentId = $values['parentId'];
    $parentName = $values['parentName'];

    if ($action == 'category updated') {
        $oldCategoryName = $values['oldCategoryName'];
        $oldCategoryPath = $values['oldCategoryPath'];
        $oldDescription = $values['oldDescription'];
        $oldParentId = $values['oldParentId'];
        $oldParentName = $values['oldParentName'];
    } elseif ($action == 'object entered category' || $action == 'object leaved category') {
        $objectName = $values['objectName'];
        $objectType = $values['objectType'];
        $objectUrl = $values['objectUrl'];
    }

    global $prefs, $user;
    $userlib = TikiLib::lib('user');
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');

    $nots = [];
    $defaultLanguage = $prefs['site_language'];

    // Users watching this gallery
    if ($prefs['feature_user_watches'] == 'y') {
        if ($action == 'category created') {
            $nots = $tikilib->get_event_watches($event, $parentId);
        } elseif ($action == 'category removed') {
            $nots = $tikilib->get_event_watches($event, $categoryId);
            $nots = array_merge($nots, $nots = $tikilib->get_event_watches($event, $parentId));
        } else {
            $nots = $tikilib->get_event_watches($event, $categoryId);
        }

        if (! empty($prefs['user_category_watch_editor']) && $prefs['user_category_watch_editor'] !== "y") {
            for ($i = count($nots) - 1; $i >= 0; --$i) {
                if ($nots[$i]['user'] == $user) {
                    unset($nots[$i]);
                    break;
                }
            }
        }

        for ($i = count($nots) - 1; $i >= 0; --$i) {
            $nots[$i]['language'] = $tikilib->get_user_preference($nots[$i]['user'], "language", $defaultLanguage);
        }

        if ($prefs['feature_daily_report_watches'] == 'y') {
            $cache_data = $values;
            $cache_data['user'] = $user;
            $cache_data['event'] = $event;
            $reportsManager = Reports_Factory::build('Reports_Manager');
            $reportsManager->addToCache($nots, $cache_data);
        }
    }

    if (count($nots)) {
        include_once(__DIR__ . '/../webmail/tikimaillib.php');

        $smarty->assign('categoryId', $categoryId);
        $smarty->assign('categoryName', $categoryName);
        $smarty->assign('categoryPath', $categoryPath);
        $smarty->assign('description', $description);
        $smarty->assign('parentId', $parentId);
        $smarty->assign('parentName', $parentName);
        $smarty->assign('mail_date', date("U"));
        $smarty->assign('author', $user);

        $nots_send = [];

        foreach ($nots as $not) {
            if (! empty($nots_send[$not['user']])) {
                break;
            }

            $mail = new TikiMail();
            $nots_send[$not['user']] = true;
            $mail->setUser($not['user']);
            $mail_data = '';

            if ($action == 'category created') {
                $mail_subject = $smarty->fetchLang($not['language'], "mail/user_watch_category_created_subject.tpl");
                $mail_data = $smarty->fetchLang($not['language'], "mail/user_watch_category_created.tpl");
            } elseif ($action == 'category removed') {
                $mail_subject = $smarty->fetchLang($not['language'], "mail/user_watch_category_removed_subject.tpl");
                $mail_data = $smarty->fetchLang($not['language'], "mail/user_watch_category_removed.tpl");
            } elseif ($action == 'category updated') {
                $smarty->assign('oldCategoryName', $oldCategoryName);
                $smarty->assign('oldCategoryPath', $oldCategoryPath);
                $smarty->assign('oldDescription', $oldDescription);
                $smarty->assign('oldParentId', $oldParentId);
                $smarty->assign('oldParentName', $oldParentName);

                $mail_subject = $smarty->fetchLang($not['language'], "mail/user_watch_category_updated_subject.tpl");
                $mail_data = $smarty->fetchLang($not['language'], "mail/user_watch_category_updated.tpl");
            } elseif ($action == 'object entered category') {
                $smarty->assign('objectName', $objectName);
                $smarty->assign('objectType', $objectType);
                $smarty->assign('objectUrl', $objectUrl);

                $mail_subject = $smarty->fetchLang($not['language'], "mail/user_watch_object_entered_category_subject.tpl");
                $mail_data = $smarty->fetchLang($not['language'], "mail/user_watch_object_entered_category.tpl");
            } elseif ($action == 'object leaved category') {
                $smarty->assign('objectName', $objectName);
                $smarty->assign('objectType', $objectType);
                $smarty->assign('objectUrl', $objectUrl);

                $mail_subject = $smarty->fetchLang($not['language'], "mail/user_watch_object_leaved_category_subject.tpl");
                $mail_data = $smarty->fetchLang($not['language'], "mail/user_watch_object_leaved_category.tpl");
            }

            if ($mail_data) {
                $mail->setSubject($mail_subject);
                $mail->setText($mail_data);
                $mail->send([$not['email']]);
            }
        }
    }
}

function sendStructureEmailNotification($params)
{
    global $prefs;
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');
    $structlib = TikiLib::lib('struct');

    $params['event'] = 'structure_' . $params['action'];

    if ($params['action'] == 'move_up' || $params['action'] == 'move_down') {
        $nots = $structlib->get_watches('', $params['parent_id'], false);
    } else {
        $nots = $structlib->get_watches('', $params['page_ref_id']);
    }

    if ($prefs['feature_daily_report_watches'] == 'y') {
        $reportsManager = Reports_Factory::build('Reports_Manager');
        $reportsManager->addToCache($nots, $params);
    }

    if (! empty($nots)) {
        $defaultLanguage = $prefs['site_language'];
        include_once(__DIR__ . '/../webmail/tikimaillib.php');
        $smarty->assign_by_ref('action', $params['action']);
        $smarty->assign_by_ref('page_ref_id', $params['page_ref_id']);

        if (! empty($params['name'])) {
            $smarty->assign('name', $params['name']);
        }

        foreach ($nots as $not) {
            $mail = new TikiMail();
            $mail->setUser($not['user']);
            $not['language'] = $tikilib->get_user_preference($not['user'], 'language', $defaultLanguage);
            $mail_subject = $smarty->fetchLang($not['language'], 'mail/user_watch_structure_subject.tpl');
            $mail_data = $smarty->fetchLang($not['language'], 'mail/user_watch_structure.tpl');
            $mail->setSubject($mail_subject);
            $mail->setText($mail_data);
            $mail->send([$not['email']]);
        }
    }
}

/**
 * @param $type Type of the object commented on, 'wiki', 'article', 'blog', 'trackeritem'
 * @param $id Identifier of the object commented on. For articles, their id and for wiki pages, their name
 * @param $title Comment title
 * @param $content Comment content
 * @param $commentId Comment ID just posted
 * @param $anonymousName Name of the user when comment is submitting by an anonymous user (or anonymously by an existing user)
 */
function sendCommentNotification($type, $id, $title, $content, $commentId, $anonymousName)
{
    global $user, $prefs;
    $smarty = TikiLib::lib('smarty');
    $tikilib = TikiLib::lib('tiki');
    $userlib = TikiLib::lib('user');

    if ($type === 'wiki page') {
        $type = 'wiki';
    }

    if ($type == 'wiki') {
        $events = 'wiki_comment_changes';
    } elseif ($type == 'article') {
        $events = 'article_commented';
    } elseif ($type == 'trackeritem') {
        $events = 'trackeritem_commented';
    // Blog comment mail
    } elseif ($type == 'blog') {
        $events = 'blog_comment_changes';
    } else {
        throw new Exception('Unknown type %0', $type);
    }

    if ($type == 'trackeritem') {
        // Tracker watches are pretty complicated, to get from trklib
        $trklib = TikiLib::lib('trk');
        $trackerId = $trklib->get_tracker_for_item($id);
        $trackerOptions = $trklib->get_tracker_options($trackerId);
        $watches = $trklib->get_notification_emails($trackerId, $id, $trackerOptions);
    } else {
    // Blog comment mail
        $watches = $tikilib->get_event_watches($events, $id);
    }

    // get individual comment reply watches
    $comments_list = TikiLib::lib('comments')->get_root_path($commentId);
    foreach ($comments_list as $threadId) {
        $watches2 = $tikilib->get_event_watches('thread_comment_replied', $threadId);
        if (! empty($watches2)) {
            // make sure we add unique email addresses to send the notification to
            foreach ($watches2 as $userWatch) {
                if (
                    ! in_array($userWatch['email'], array_map(function ($w) {
                        return $w['email'];
                    }, $watches))
                ) {
                    $watches[] = $userWatch;
                }
            }
        }
    }

    if ($type != 'wiki' || $prefs['wiki_watch_editor'] != 'y') {
        for ($i = count($watches) - 1; $i >= 0; --$i) {
            if ($watches[$i]['user'] == $user) {
                unset($watches[$i]);
                break;
            }
        }
    }

    if ($prefs['notify_oneself'] == 'y' && ! in_array($user, array_column($watches, 'user'))) {
        $watches[] = [
            'email' => $userlib->get_user_email($user),
            'user' => $user,
            'language' => $userlib->get_user_preference($user, 'language', $prefs['site_language']),
            'watchId' => $userlib->get_user_id($user)
        ];
    }

    if (count($watches)) {
        if ($type == 'wiki') {
            $smarty->assign('mail_objectname', $id);
        } elseif ($type == 'article') {
            $artlib = TikiLib::lib('art');
            $smarty->assign('mail_objectname', $artlib->get_title($id));
        } elseif ($type == 'trackeritem') {
            if ($prefs['feature_daily_report_watches'] == 'y') {
                $reportsManager = Reports_Factory::build('Reports_Manager');
                $reportsManager->addToCache(
                    $watches,
                    [
                        'event' => 'tracker_item_comment',
                        'itemId' => $id,
                        'trackerId' => $trackerId,
                        'user' => $user,
                        'threadId' => $commentId
                    ]
                );
            }

            $tracker = $trklib->get_tracker($trackerId);
            $smarty->assign('mail_objectname', $tracker['name']);
            $smarty->assign('mail_item_title', $trklib->get_isMain_value($trackerId, $id));
        } elseif ($type == 'blog') {
            $bloglib = TikiLib::lib('blog');
            $blog_post = $bloglib->get_post($id);
            $smarty->assign('mail_objectname', $blog_post['title']);
        }

        // General comment mail
        $smarty->assign('mail_objectid', $id);
        $smarty->assign('objecttype', $type);
        $smarty->assign('mail_user', empty($anonymousName) ? $user : $anonymousName);
        $smarty->assign('mail_title', $title);
        $smarty->assign('mail_comment', $content);
        $smarty->assign('comment_id', $commentId);

        $additionalHeaders = \Tiki\Notifications\Email::getEmailThreadHeaders($type, $commentId);

        if ($prefs['feature_comments_send_author_name'] == 'y') {
            if (! empty($anonymousName)) {
                $fromName = $anonymousName;
            } else {
                $fromName = $tikilib->get_user_preference($user, 'realName');
                if (empty($fromName)) {
                    $fromName = $user;
                }
            }
            return sendEmailNotification($watches, null, 'user_watch_comment_subject.tpl', null, 'user_watch_comment.tpl', '', $fromName, $additionalHeaders);
        } else {
            return sendEmailNotification($watches, null, 'user_watch_comment_subject.tpl', null, 'user_watch_comment.tpl', '', '', $additionalHeaders);
        }
    }

    return 0;
}

function sendSwitchUserNotification($adminUser, $targetUser)
{
    global $user, $prefs;
    $userlib = TikiLib::lib('user');
    $tikilib = TikiLib::lib('tiki');

    $targetUserEmail = $userlib->get_user_email($targetUser);

    if (empty($targetUserEmail)) {
        return;
    }

    $smarty = TikiLib::lib('smarty');
    $smarty->assign('admin_user', $adminUser);
    $smarty->assign('target_user', $targetUser);
    $smarty->assign('action', 'switch');

    $additionalHeaders = [];

    $watches[] = [
        'email' => $targetUserEmail,
        'user' => $targetUser,
        'language' => $userlib->get_user_preference($user, 'language', $prefs['site_language']),
        'watchId' => $userlib->get_user_id($user)
    ];

    sendEmailNotification($watches, null, 'user_switched_notification_subject.tpl', null, 'user_switched_notification.tpl', '', $adminUser, $additionalHeaders);
}
