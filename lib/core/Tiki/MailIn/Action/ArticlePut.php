<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\MailIn\Action;

use Tiki\MailIn\Account;
use Tiki\MailIn\Source\Message;
use TikiLib;

class ArticlePut implements ActionInterface
{
    private $topicId;
    private $type;

    public function __construct(array $params)
    {
        $this->topicId = isset($params['topic']) ? (int)$params['topic'] : 0;
        $this->type = isset($params['type']) ? (int)$params['type'] : null;
    }

    public function getName()
    {
        return tr('Submit Article');
    }

    public function isEnabled()
    {
        global $prefs;

        return $prefs['feature_submissions'] == 'y';
    }

    public function isAllowed(Account $account, Message $message)
    {
        $user = $message->getAssociatedUser();
        $perms = TikiLib::lib('tiki')->get_user_permission_accessor($user, 'topic', $this->topicId);

        if (! $perms->submit_article && ! $perms->edit_submission) {
            return false;
        }

        return true;
    }

    public function execute(Account $account, Message $message)
    {
        $artlib = TikiLib::lib('art');
        $tikilib = TikiLib::lib('tiki');

        $title = $message->getSubject();
        $heading = $message->getBody();
        $topicId = $this->topicId;
        $userm = $message->getAssociatedUser();
        $authorName = $userm;
        $body = '';
        $publishDate = $tikilib->now;
        $cur_time = explode(',', $tikilib->date_format('%Y,%m,%d,%H,%M,%S', $publishDate));
        $expireDate = $tikilib->make_time($cur_time[3], $cur_time[4], $cur_time[5], $cur_time[1], $cur_time[2], $cur_time[0] + 1);
        $subId = 0;
        $type = $this->type;
        $useImage = 'n';
        $image_x = '';
        $image_y = '';
        $imgname = '';
        $imgsize = '';
        $imgtype = '';
        $imgdata = '';
        $topline = '';
        $subtitle = '';
        $linkto = '';
        $image_caption = '';
        $lang = '';
        $rating = 7;
        $isfloat = 'n';

        $subid = $artlib->replace_submission($title, $authorName, $topicId, $useImage, $imgname, $imgsize, $imgtype, $imgdata, $heading, $body, $publishDate, $expireDate, $userm, $subId, $image_x, $image_y, $type, $topline, $subtitle, $linkto, $image_caption, $lang, $rating, $isfloat);

        $perms = TikiLib::lib('tiki')->get_user_permission_accessor($user, 'topic', $this->topicId);
        if ($perms->autoapprove_submission) {
            $artlib->approve_submission($subid);
        }

        return true;
    }
}
