<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Class for forum_post_thread events
 */
class Reports_Send_EmailBuilder_ForumPostThread extends Reports_Send_EmailBuilder_Abstract
{
    public function getTitle()
    {
        return tr('New replies in forum topics:');
    }

    public function getOutput(array $change)
    {
        $base_url = $change['data']['base_url'];

        $commentslib = TikiLib::lib('comments');
        $parent_topic = $commentslib->get_comment($change['data']['topicId']);

        if (! empty($parent_topic['title'])) {
            $topicTitle = $parent_topic['title'];
        } else {
            $topicTitle = tr('Topic name not found (#%0)', $change['data']['topicId']);
        }

        $output = tr(
            '%0 <a href="%1">replied</a> to the topic %2 at forum %3.',
            "<u>{$change['data']['user']}</u>",
            "\"{$base_url}tiki-view_forum_thread.php?comments_parentId={$change['data']['topicId']}#threadId={$change['data']['threadId']}\"",
            "<a href=\"{$base_url}tiki-view_forum_thread.php?comments_parentId={$change['data']['topicId']}&forumId={$change['data']['forumId']}\">{$topicTitle}</a>",
            "<a href=\"{$base_url}tiki-view_forum.php?forumId={$change['data']['forumId']}\">{$change['data']['forumName']}</a>"
        );

        return $output;
    }
}
