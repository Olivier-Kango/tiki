<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// NOTE : This controller excludes anything related to the comments. Previous code mixed comments and forums
//        as they use the same storage. However, the way the are interacted with is so different that the code
//        needs to remain separate to keep everything simple.

class Services_Comment_Controller
{
    /**
     * Lists comments for an object
     *
     * @param JitFilter $input
     *
     * @return array
     * @throws Services_Exception
     */
    public function action_list($input)
    {
        global $prefs;

        $type = $input->type->alphaspace();
        if ($type === 'wiki page') {
            $objectId = $input->objectId->pagename();
        } else {
            $objectId = $input->objectId->digits();
        }

        $objectlib = TikiLib::lib('object');
        if ($objectId !== $input->objectId->none() || ! $objectlib->isValidObject($type, $objectId)) {
            $objectId = $input->objectId->xss();
            throw new Services_Exception(tr('Invalid %0 ID: %1', $type, $objectId), 403);
        }

        if (! $this->isEnabled($type, $objectId)) {
            throw new Services_Exception(tr('Comments not allowed on this page.'), 403);
        }

        if (! $this->canView($type, $objectId)) {
            throw new Services_Exception(tr('Permission denied.'), 403);
        }

        $commentslib = TikiLib::lib('comments');
        // TODO : Add sorting, thread style, moderation, ...
        $offset = $input->offset->int();
        $maxRecords = $input->maxRecords->int();
        $maxRecords = $maxRecords ? $maxRecords : $prefs['comments_per_page'];
        $sortMode = $prefs['comments_sort_mode'];

        $comments = $commentslib->get_comments("$type:$objectId", null, $offset, $maxRecords, $sortMode);

        $this->markEditable($comments['data']);
        $response = [
            'title'             => tr('Comments'),
            'comments'          => $comments['data'],
            'type'              => $type,
            'objectId'          => $objectId,
            'parentId'          => 0,
            'cant'              => $comments['cant'],
            'offset'            => $offset,
            'maxRecords'        => $maxRecords,
            'sortMode'          => $sortMode,
            'allow_post'        => $this->canPost($type, $objectId) && ! $input->hidepost->int(),
            'allow_remove'      => $this->canRemove($type, $objectId),
            'allow_lock'        => $this->canLock($type, $objectId),
            'allow_unlock'      => $this->canUnlock($type, $objectId),
            'allow_archive'     => $this->canArchive($type, $objectId),
            'allow_moderate'    => $this->canModerate($type, $objectId),
            'allow_vote'        => $this->canVote($type, $objectId),
        ];

        $paginationOnClick = "
                var \$commentContainer = $(this).parents('.comment-container');
                \$commentContainer
                    .tikiModal(tr('Loading...'))
                    .load(
                        \$.service('comment', 'list'),
                        {type:'$type', objectId:'$objectId', offset: comment_offset},
                        function () {
                            \$('html, body').animate({
                                    scrollTop: \$commentContainer.offset().top
                                }, 2000, function () {
                                    \$commentContainer.tikiModal();
                                });
                        }
                    );
                return false;";

        if (! TIKI_API) {
            $response['paginationOnClick'] = str_replace(["\n", "\t"], '', $paginationOnClick);
        }

        return $response;
    }

    public function action_post($input)
    {
        global $prefs, $user;

        $type = $input->type->text();
        $objectId = $input->objectId->pagename();
        $parentId = $input->parentId->int();
        $return_url = $input->return_url->url();
        $version = $input->version->int();

        // Check general permissions

        if (! $this->isEnabled($type, $objectId)) {
            throw new Services_Exception(tr('Comments not allowed on this page.'), 403);
        }

        if (! $this->canPost($type, $objectId)) {
            throw new Services_Exception(tr('Permission denied.'), 403);
        }

        $objectlib = TikiLib::lib('object');
        if (! $objectlib->isValidObject($type, $objectId)) {
            $objectId = $input->objectId->xss();
            throw new Services_Exception(tr('Invalid %0 ID: %1', $type, $objectId), 403);
        }

        $commentslib = TikiLib::lib('comments');
        if ($parentId && $prefs['feature_comments_locking'] == 'y') {
            $parent = $commentslib->get_comment($parentId);

            if ($parent['locked'] == 'y') {
                throw new Services_Exception(tr('Parent is locked.'), 403);
            }
        }

        $errors = [];

        $title = trim($input->title->text());
        $data = trim($input->data->wikicontent());

        $tikilib = TikiLib::lib('tiki');
        $data = $tikilib->convertAbsoluteLinksToRelative($data);

        $watch = $input->watch->text();
        $contributions = [];
        $anonymous_name = '';
        $anonymous_email = '';
        $anonymous_website = '';
        $diffInfo = []; // for saveAndComment

        if (empty($user) || $prefs['feature_comments_post_as_anonymous'] == 'y') {
            $anonymous_name = $input->anonymous_name->text();
            $anonymous_email = $input->anonymous_email->email();
            $anonymous_website = $input->anonymous_website->url();
        }

        if ($input->post->int()) {
            // Validate

            if (empty($user)) {
                if (empty($anonymous_name)) {
                    $errors['anonymous_name'] = tr('Name must be specified');
                }
            }

            if (! empty($anonymous_name) && empty($anonymous_email)) {
                $errors['anonymous_emal'] = tr('Email must be specified');
            }

            if ($prefs['comments_notitle'] != 'y' && empty($title)) {
                $errors['title'] = tr('Title is empty');
            }

            if (empty($data)) {
                $errors['data'] = tr('Content is empty');
            }

            if (empty($user) && $prefs['feature_antibot'] == 'y') {
                $captchalib = TikiLib::lib('captcha');

                if (! $captchalib->validate($input->none())) {
                    $errors[] = $captchalib->getErrors();
                }
            }

            if ($prefs['comments_notitle'] == 'y') {
                $title = 'Untitled ' . TikiLib::lib('tiki')->get_long_datetime(TikiLib::lib('tikidate')->getTime());
            }


            if (count($errors) === 0) {
                $message_id = ''; // By ref
                $threadId = $commentslib->post_new_comment(
                    "$type:$objectId",
                    $parentId,
                    $user,
                    $title,
                    $data,
                    $message_id,
                    isset($parent['message_id']) ? $parent['message_id'] : '',
                    'n',
                    '',
                    '',
                    $contributions,
                    $anonymous_name,
                    '',
                    $anonymous_email,
                    $anonymous_website,
                    [],
                    $version
                );
                if ($threadId) {
                    switch ($type) {
                        case 'wiki page':
                            $watch_event = 'wiki_comment_changes';

                            $wikilib = TikiLib::lib('wiki');
                            $parent_name = $objectId;
                            $notification_url = $wikilib->sefurl($objectId);
                            break;
                        case 'article':
                            $watch_event = 'article_comment_changes';

                            $artlib = TikiLib::lib('art');
                            $parent_name = $artlib->get_title($objectId);
                            $notification_url = 'tiki-read_article.php?articleId=' . $objectId;
                            break;

                        case 'trackeritem':
                            $watch_event = 'tracker_item_modified';

                            $trk = TikiLib::lib('trk');
                            $trackerId = $trk->get_tracker_for_item($objectId);
                            $parent_name = $trk->get_isMain_value($trackerId, $objectId);
                            $notification_url = 'tiki-view_tracker_item.php?itemId=' . $objectId;
                            break;

                        case 'blog post':
                            $watch_event = 'blog_comment_changes';

                            $bloglib = TikiLib::lib('blog');
                            $blog_post = $bloglib->get_post($objectId);
                            $parent_name = $blog_post['title'];
                            $notification_url = 'tiki-view_blog_post.php?postId=' . $objectId;
                            break;

                        default:
                            $parent_name = '';
                            $notification_url = '';
                            break;
                    }
                    Feedback::showWatchers($watch_event, $objectId, 'thread_comment_replied');
                    Feedback::sendHeaders();

                    // Set watch if requested
                    if ($prefs['feature_user_watches'] == 'y' && $watch == 'y') {
                        // ensure subcomments are not watched when parent comments are watched
                        // so we don't fill the user_watches table unnecessary
                        $comments_list = $commentslib->get_root_path($threadId);
                        $watch_user = empty($anonymous_email) ? $user : $anonymous_name . ' ' . tra('(not registered)');
                        if (! TikiLib::lib('tiki')->get_user_event_watches($watch_user, 'thread_comment_replied', $comments_list)) {
                            if (! empty($anonymous_email)) { // Add an anonymous watch, if email address supplied.
                                TikiLib::lib('tiki')->add_user_watch(
                                    $anonymous_name . ' ' . tra('(not registered)'),
                                    'thread_comment_replied',
                                    $threadId,
                                    'comment',
                                    $parent_name . ':' . $title,
                                    $notification_url,
                                    $anonymous_email
                                );
                            } elseif ($user) {
                                TikiLib::lib('tiki')->add_user_watch(
                                    $user,
                                    'thread_comment_replied',
                                    $threadId,
                                    'comment',
                                    $parent_name . ':' . $title,
                                    $notification_url
                                );
                            }
                        }
                    }
                }

                $feedback = [];

                if ($prefs['feature_comments_moderation'] === 'y' && ! $this->canModerate($type, $objectId)) {
                    $feedback[] = tr('Your message has been queued for approval and will be posted after a moderator approves it.');
                }

                if ($threadId) {
                    $this->rememberCreatedComment($threadId);

                    $emailType = '';
                    if ($prefs['wiki_watch_comments'] == 'y' && $type == 'wiki page') {
                        $emailType = 'wiki';
                    } elseif ($type == 'article') {
                        $emailType = 'article';
                    } elseif ($prefs['feature_blogs'] == 'y' && $type == 'blog post') { // Blog comment mail
                        $emailType = 'blog';
                    } elseif ($type == 'trackeritem') {
                        $emailType = 'trackeritem';
                    }
                    if ($emailType) {
                        require_once(__DIR__ . '/../../../notifications/notificationemaillib.php');
                        sendCommentNotification($emailType, $objectId, $title, $data, $threadId, $anonymous_name);
                    }

                    $access = TikiLib::lib('access');
                    if ($return_url && ! $access->is_xml_http_request()) {
                        $access->redirect($return_url, tr('Your comment was posted.'));
                    }


                    return [
                        'threadId' => $threadId,
                        'parentId' => $parentId,
                        'type' => $type,
                        'objectId' => $objectId,
                        'feedback' => $feedback,
                    ];
                }
            }
        } elseif ($version) {   // not the post
            $diffInfo = $this->setUpDiffInfo($type, $objectId, $version);
        }
        return [
            'parentId' => $parentId,
            'type' => $type,
            'objectId' => $objectId,
            'title' => $title,
            'data' => $data,
            'contributions' => $contributions,
            'anonymous_name' => $anonymous_name,
            'anonymous_email' => $anonymous_email,
            'anonymous_website' => $anonymous_website,
            'errors' => $errors,
            'return_url' => $return_url,
            'version' => $version,
            'diffInfo' => $diffInfo,
        ];
    }

    public function action_edit($input)
    {
        $threadId = $input->threadId->int();

        if (! $comment = $this->getCommentInfo($threadId)) {
            throw new Services_Exception_NotFound();
        }

        if (! $this->canEdit($comment)) {
            throw new Services_Exception_Denied();
        }

        $diffInfo = []; // for saveAndComment
        $errors = [];

        if ($input->edit->int()) {
            $title = trim($input->title->text());
            $data = trim($input->data->wikicontent());

            $tikilib = TikiLib::lib('tiki');
            $data = $tikilib->convertAbsoluteLinksToRelative($data);

            if (empty($data)) {
                $errors['data'] = tr('Content is empty');
            }

            if (count($errors) === 0) {
                $commentslib = TikiLib::lib('comments');
                $commentslib->update_comment($threadId, $title, $comment['comment_rating'], $data);

                return [
                    'threadId' => $threadId,
                    'comment' => $comment,
                ];
            }
        } elseif (! empty($comment['version']) && $comment['version']) {    // not the post
            $diffInfo = $this->setUpDiffInfo($comment['objectType'], $comment['object'], $comment['version']);
        }

        return [
            'comment' => $comment,
            'diffInfo' => $diffInfo,
            'errors' => $errors,
            'type' => $comment['objectType'],
            'objectId' => $comment['object'],
        ];
    }

    public function action_remove($input)
    {
        global $prefs, $user;

        $threadId = $input->threadId->int();
        $confirmation = $input->confirm->int();
        $status = '';

        if ($comment = $this->getCommentInfo($threadId)) {
            $type = $comment['objectType'];
            $object = $comment['object'];

            if (! $this->canRemove($type, $object)) {
                throw new Services_Exception(tr('Permission denied.'), 403);
            }

            if ($confirmation) {
                $commentslib = TikiLib::lib('comments');
                $commentslib->remove_comment($threadId);

                if ($prefs['feature_user_watches'] && $user) {
                    TikiLib::lib('tiki')->remove_user_watch_object(
                        'thread_comment_replied',
                        $threadId,
                        'comment'
                    );
                }

                $status = 'DONE';
            }
        } else {
            $status = 'DONE'; // Already gone
        }


        return [
            'threadId' => $threadId,
            'status' => $status,
            'objectType' => $type,
            'objectId' => $object,
            'parsed' => $comment['parsed'],
        ];
    }

    public function action_lock($input)
    {
        return $this->executeActionLock($input, 'lock');
    }

    public function action_unlock($input)
    {
        return $this->executeActionLock($input, 'unlock');
    }

    private function executeActionLock($input, $mode)
    {
        global $prefs;
        $type = $input->type->text();
        $objectId = $input->objectId->pagename();
        $confirmation = $input->confirm->int();
        $status = '';

        if (empty($type)) {
            throw new Services_Exception_MissingValue('type');
        }

        if (empty($objectId)) {
            throw new Services_Exception_MissingValue('objectId');
        }

        $objectlib = TikiLib::lib('object');
        if (! $objectlib->isValidObject($type, $objectId)) {
            throw new Services_Exception(tr('Invalid %0 ID: %1', $type, $objectId), 403);
        }

        if (! $this->isEnabled($type, $objectId)) {
            throw new Services_Exception(tr('Comments not allowed on this page.'), 403);
        }

        if ($prefs['feature_comments_locking'] != 'y') {
            throw new Services_Exception(tr('Comments locking feature is not enabled.'), 403);
        }

        $perms = $this->getApplicablePermissions($type, $objectId);
        if (! $perms->lock_comments) {
            throw new Services_Exception(tr('Permissions denied.'), 403);
        }

        $commentslib = TikiLib::lib('comments');
        $isLocked = $commentslib->is_object_locked("$type:$objectId");

        if ($mode === 'lock' && $isLocked) {
            throw new Services_Exception(tr('Comments already locked.'), 403);
        }

        if ($mode === 'unlock' && ! $isLocked) {
            throw new Services_Exception(tr('Comments already unlocked.'), 403);
        }

        if ($confirmation) {
            $method = $mode . '_object_thread';

            $commentslib = TikiLib::lib('comments');
            $commentslib->$method("$type:$objectId");
            $status = 'DONE';
        }

        if ($mode === 'lock') {
            $title = tr('Lock comments');
        } else {
            $title = tr('Unlock comments');
        }

        return [
            'title' => $title,
            'type' => $type,
            'objectId' => $objectId,
            'status' => $status,
        ];
    }

    public function action_moderate($input)
    {
        $threadId = $input->threadId->int();
        $confirmation = $input->confirm->int();
        $do = $input->do->alpha();
        $status = '';

        if (! $comment = $this->getCommentInfo($threadId)) {
            throw new Services_Exception(tr('Comment not found.'), 404);
        }
        $type = $comment['objectType'];
        $object = $comment['object'];

        if ($comment['approved'] == 'y') {
            throw new Services_Exception(tr('Comment already approved.'), 403);
        }

        if (! $this->canModerate($type, $object)) {
            throw new Services_Exception(tr('Permission denied.'), 403);
        }

        $commentslib = TikiLib::lib('comments');

        if ($do == 'approve') {
            if ($confirmation) {
                $status = 'DONE';
                $commentslib->approve_comment($threadId);
            }
        } elseif ($do == 'reject') {
            if ($confirmation) {
                $status = 'DONE';
                $commentslib->reject_comment($threadId);
            }
        } else {
            throw new Exception(tr('Invalid argument.'), 500);
        }

        return [
            'threadId' => $threadId,
            'type' => $type,
            'objectId' => $object,
            'status' => $status,
            'do' => $do,
        ];
    }

    public function action_archive($input)
    {
        $threadId = $input->threadId->int();
        $do = $input->do->alpha();
        $confirmation = $input->confirm->int();
        $status = '';

        if (! $comment = $this->getCommentInfo($threadId)) {
            throw new Services_Exception(tr('Comment not found.'), 404);
        }

        $type = $comment['objectType'];
        $object = $comment['object'];

        if (! $this->canArchive($type, $object)) {
            throw new Services_Exception(tr('Permission denied.'), 403);
        }

        if ($confirmation) {
            $status = 'DONE';

            $commentslib = TikiLib::lib('comments');
            if ($do == 'archive') {
                $commentslib->archive_thread($threadId);
            } else {
                $commentslib->unarchive_thread($threadId);
            }
        }

        return [
            'threadId' => $threadId,
            'type' => $type,
            'objectId' => $object,
            'status' => $status,
            'do' => $do,
        ];
    }

    public function action_deliberation_item($input)
    {
        return [];
    }

    public function canView($type, $objectId)
    {
        // Note: $perms provides a magic method __get as an accessor for attributes.
        // I.e. $perms->wiki_view_comments or $perms->tracker_view_comments are returend by that accessor method
        // and do not exist as a property.
        // Wether they are true or false depends on the assigned permissions stored in $perms->resolver
        // for the respective groups.

        $perms = $this->getApplicablePermissions($type, $objectId);

        switch ($type) {
            case 'wiki page':
                return $perms->wiki_view_comments;
                break;

            // canPost() requires also view access frontend/template wise.
            // So we return also true if post ($perms->comment_tracker_items) is enabled.
            case 'trackeritem':
                $item = Tracker_Item::fromId($objectId);
                if ($item) {
                    return $item->canViewComments();
                } else {
                    return ($perms->tracker_view_comments || $perms->comment_tracker_items);
                }
                break;


            // @TODO which $types do use / or should use these permissions?
            // taken from the prevoius developer: seems that view should be automatically assigned if edit / post is granted.
            default:
                if (! ($perms->read_comments || $perms->post_comments || $perms->edit_comments)) {
                    return false;
                }
                break;
        }

        return true;
    }


    public function canPost($type, $objectId)
    {
        global $prefs;

        // see comment about $perms in canView().

        $perms = $this->getApplicablePermissions($type, $objectId);

        if ($prefs['feature_comments_locking'] == 'y' &&  TikiLib::lib('comments')->is_object_locked("$type:$objectId")) {
            return false;
        }

        switch ($type) {
            // requires also view access from the front/template part
            // so we add $perms->comment_tracker_items also to canView()
            case 'trackeritem':
                $item = Tracker_Item::fromId($objectId);
                if ($item) {
                    return $item->canPostComments();
                } else {
                    return $perms->comment_tracker_items;
                }
                break;

            // @TODO which $types do use / or should use these permissions?
            default:
                if (! ($perms->post_comments)) {
                    return false;
                }
                break;
        }


        return true;
    }


    public function isEnabled($type, $objectId)
    {
        global $prefs;

        switch ($type) {
            case 'wiki page':
                if ($prefs['feature_wiki_comments'] != 'y') {
                    return false;
                }

                if ($prefs['wiki_comments_allow_per_page'] == 'y') {
                    $info = TikiLib::lib('tiki')->get_page_info($objectId);
                    if (! empty($info['comments_enabled'])) {
                        return $info['comments_enabled'] == 'y';
                    }
                }

                return true;
            case 'file gallery':
                return $prefs['feature_file_galleries_comments'] == 'y';
            case 'poll':
                return $prefs['feature_poll_comments'] == 'y';
            case 'faq':
                return $prefs['feature_faq_comments'] == 'y';
            case 'blog post':
                return $prefs['feature_blogposts_comments'] == 'y';
            case 'trackeritem':
                return true;
            case 'article':
                return $prefs['feature_article_comments'] == 'y';
            case 'activity':
                return $prefs['activity_basic_events'] == 'y' || $prefs['activity_custom_events'] == 'y' || $prefs['monitor_enabled'] == 'y';
            default:
                return false;
        }
    }

    private function getCommentInfo($threadId)
    {
        if (! $threadId) {
            throw new Services_Exception(tr('Thread not specified.'), 500);
        }

        $commentslib = TikiLib::lib('comments');
        $comment = $commentslib->get_comment($threadId);

        if ($comment) {
            $type = $comment['objectType'];
            $object = $comment['object'];

            if (! $this->isEnabled($type, $object)) {
                throw new Services_Exception(tr('Comments not allowed on this page.'), 403);
            }

            return $comment;
        }
    }

    private function canLock($type, $objectId)
    {
        global $prefs;

        if ($prefs['feature_comments_locking'] != 'y') {
            return false;
        }

        $perms = $this->getApplicablePermissions($type, $objectId);

        if (! $perms->lock_comments) {
            return false;
        }

        $commentslib = TikiLib::lib('comments');
        return ! $commentslib->is_object_locked("$type:$objectId");
    }

    private function canUnlock($type, $objectId)
    {
        global $prefs;

        if ($prefs['feature_comments_locking'] != 'y') {
            return false;
        }

        $perms = $this->getApplicablePermissions($type, $objectId);

        if (! $perms->lock_comments) {
            return false;
        }

        $commentslib = TikiLib::lib('comments');
        return $commentslib->is_object_locked("$type:$objectId");
    }

    private function canArchive($type, $objectId)
    {
        global $prefs;

        if ($prefs['comments_archive'] != 'y') {
            return false;
        }

        $perms = $this->getApplicablePermissions($type, $objectId);

        return $perms->admin_comments;
    }

    private function canRemove($type, $objectId)
    {
        $perms = $this->getApplicablePermissions($type, $objectId);
        return $perms->remove_comments;
    }

    private function canVote($type, $objectId)
    {
        global $prefs;

        if ($prefs['wiki_comments_simple_ratings'] !== 'y') {
            return false;
        }

        $perms = $this->getApplicablePermissions($type, $objectId);
        return $perms->vote_comments || $perms->admin_comments;
    }

    private function canModerate($type, $objectId)
    {
        global $prefs;

        if ($prefs['feature_comments_moderation'] != 'y') {
            return false;
        }

        $perms = $this->getApplicablePermissions($type, $objectId);

        return $perms->admin_comments;
    }

    private function markEditable(&$comments)
    {
        foreach ($comments as & $comment) {
            $comment['can_edit'] = $this->canEdit($comment);

            if ($comment['replies_info']['numReplies'] > 0) {
                $this->markEditable($comment['replies_info']['replies']);
            }
        }
    }

    private function canEdit(array $comment)
    {
        global $prefs, $user, $tiki_p_admin;

        if ($tiki_p_admin == 'y') {
            return true;
        }

        if ($prefs['comments_allow_correction'] != 'y') {
            return false;
        }

        $tikilib = TikiLib::lib('tiki');
        $editionTimeout = (int) $prefs['comments_correction_timeout'] * 60;

        if ($comment['commentDate'] < $tikilib->now - $editionTimeout) {
            return false;
        }

        if ($comment['userName'] == $user) {
            return true;
        }

        // Handles comments created by anonymous users
        if (isset($_SESSION['created_comments']) && in_array($comment['threadId'], $_SESSION['created_comments'])) {
            return true;
        }

        return false;
    }

    private function getApplicablePermissions($type, $objectId)
    {
        switch ($type) {
            case 'trackeritem':
                $item = Tracker_Item::fromId($objectId);
                if ($item) {
                    return $item->getPerms();
                } else {
                    Feedback::error(tr('Comment permissions: %0 object %1 not found', $type, $objectId));
                    // return global perms
                    return Perms::get();
                }
            default:
                return Perms::get($type, $objectId);
        }
    }

    private function rememberCreatedComment($threadId)
    {
        if (! isset($_SESSION['created_comments'])) {
            $_SESSION['created_comments'] = [];
        }

        $_SESSION['created_comments'][] = $threadId;
    }

    /**
     * @param $type
     * @param $objectId
     * @param $version
     * @return mixed
     * @throws Exception
     */
    private function setUpDiffInfo($type, $objectId, $version)
    {
        if ($type === 'trackeritem') {    // for saveAndComment
            $trackerLib = TikiLib::lib('trk');

            $history = $trackerLib->get_item_history(
                ['itemId' => $objectId],
                0,
                ['version' => $version]
            );

            $diffInfo = [];

            foreach ($history['data'] as $info) {
                $field_info = $trackerLib->get_field_info($info['fieldId']);
                $info['fieldName'] = $field_info['name'];
                $diffInfo[] = $info;
            }
        }
        // add some specific js to set up comment post form in a modal dialog
        // so it can refresh the page after the post
        TikiLib::lib('header')->add_jq_onready(/** @lang JavaScript */
            '
$(".comment-post").parents("form").off("submit").on("submit", ajaxSubmitEventHandler(function (data) {
    $.closeModal();
    location.href = location.href.replace(/#.*$/, "");
}));
            '
        );
        return $diffInfo;
    }
}
