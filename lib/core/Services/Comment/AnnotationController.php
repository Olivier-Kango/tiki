<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Class Services_Annotator_Controller
 *
 * Linking annotatorjs to inline comments
 */

const RANGE_ATTRIBUTE = 'tiki.comment.ranges';

class Services_Comment_AnnotationController
{
    /** @var  Comments */
    private $commentslib;
    /** @var  Services_Comment_Controller */
    private $commentController;


    /**
     * @throws Exception
     * @throws Services_Exception_Disabled
     */
    public function setUp()
    {
        Services_Exception_Disabled::check('comments_inline_annotator');

        $this->commentslib = TikiLib::lib('comments');
        $this->commentController = new Services_Comment_Controller();
    }

    /**
     * Create an inline comment
     *
     * @param jitFilter $input
     *        string    json encoded comment info from annotatorjs
     * containing:
     *        string    text    comment text
     *        string    quote   quoted text on page
     *        array     ranges  range info for quoted text
     *        string    uri     actually object-type:object-id identifier for the tiki object to be commented
     *
     * @return array    unused probably
     *
     * @throws Exception
     * @throws Services_Exception_Denied
     */

    public function action_create($input)
    {
        global $user;

        // annotatejs sends the params in the request payload by default, so we use option emulateJSON
        // but then need to decode the json string here
        $params = new jitFilter(json_decode($input->json->none(), true));

        $text = $params->text->wikicontent();
        $quote = $params->quote->text();
        $ranges = $params->asArray('ranges');
        $identifier = urldecode($params->uri->url());   // not really the uri but object-type:object-id identifier
        list($objectType, $objectId) = explode(':', $identifier, 2);

        if (! $this->commentController->canPost($objectType, $objectId)) {
            throw new Services_Exception_Denied();
        }

        $comment = $this->formatComment($quote, $text);
        $title = $this->createTitle($text);
        $messageId = '';

        // create the comment
        $threadId = $this->commentslib->post_new_comment(
            $identifier,
            0,
            $user,
            $title,
            $comment,
            $messageId
        );

        TikiLib::lib('attribute')->set_attribute(
            'comment',
            $threadId,
            RANGE_ATTRIBUTE,
            json_encode($ranges)
        );

        require_once(__DIR__ . '/../../../notifications/notificationemaillib.php');
        sendCommentNotification($objectType, $objectId, $title, $comment, $threadId, '');

        return [
            'id' => $threadId,
        ];
    }

    /**
     * Update an inline comment
     *
     * @param jitFilter $input
     *        string    json encoded comment info from annotatorjs
     * containing:
     *        int       id      comment threadId
     *        string    text    comment text
     *        string    quote   quoted text on page
     *        array     ranges  range info for quoted text
     *        string    uri     actually object-type:object-id identifier for the tiki object to be commented
     *        string    created datetime updated (?)
     *        string    updated datetime updated
     *
     * @return array    unused probably
     *
     * @throws Exception
     * @throws Services_Exception_Denied
     * @throws Services_Exception_NotFound
     */

    public function action_update($input)
    {
        $threadId = $input->threadId->int();
        $params = new jitFilter(json_decode($input->json->none(), true));

        $ranges = $params->asArray('ranges');
        $text = $params->text->wikicontent();
        $quote = $params->quote->text();

        $input->offsetSet('data', $this->formatComment($quote, $text));
        $input->offsetSet('title', $this->createTitle($text));
        $input->offsetSet('edit', 1);

        $ret = $this->commentController->action_edit($input);

        if ($ret) {
            TikiLib::lib('attribute')->set_attribute(
                'comment',
                $threadId,
                RANGE_ATTRIBUTE,
                json_encode($ranges)
            );
        }

        return $ret;
    }

    /**
     * Remove an inline comment - warning, no confirmation yet
     *
     * @param jitFilter $input
     *        int       threadId  comment id to delete
     *
     * @return array
     * @throws Services_Exception
     */

    public function action_destroy($input)
    {
        $input->offsetSet('confirm', 1);    // TODO but not sure how?

        $ret = $this->commentController->action_remove($input);

        $ret['id'] = $ret['threadId'];
        return $ret;
    }

    /**
     * List inline comments for a tiki object
     *
     * @param jitFilter $input
     *        int       limit   page size TODO
     *        int       offset  page start
     *        string    uri     object-type:object-id identifier for the tiki object to search
     *
     * @return array    [total, rows]
     * @throws Exception
     * @throws Services_Exception
     */

    public function action_search($input)
    {
        global $prefs;

        $tikilib = TikiLib::lib('tiki');
        $userlib = TikiLib::lib('user');

        $limit = $input->limit->int();      // unused so far
        $offset = $input->offset->int();    // TODO pagination

        $identifier = urldecode($input->uri->url());
        $object = explode(':', $identifier);

        $list = $this->commentController->action_list(new jitFilter([
            'type' => $object[0],
            'objectId' => $object[1],
        ]));

        $comments = [];

        foreach ($list['comments'] as $comment) {
            if (strpos($comment['data'], ';note:') === 0) {         // only the "inline" ones starting ;note: so far
                $data = explode("\n", $comment['data'], 2);
                $quote = trim(substr($data[0], 6));
                $text = trim($data[1]);

                $ranges = json_decode(
                    TikiLib::lib('attribute')->get_attribute(
                        'comment',
                        $comment['threadId'],
                        RANGE_ATTRIBUTE
                    ),
                    true
                );

                $permissions = [
                    'create' => $list['allow_post'],
                    'update' => $comment['can_edit'],
                    'delete' => $list['allow_remove'],
                ];

                $annotation = [
                    'id'          => $comment['threadId'],
                    'text'        => $text,
                    'quote'       => $quote,
                    'created'     => $tikilib->get_iso8601_datetime($comment['commentDate']),
                    'updated'     => $tikilib->get_iso8601_datetime($comment['commentDate']),   // we don't have a commentUpdated column?
                    'ranges'      => $ranges ? $ranges : [],
                    'permissions' => $permissions,
                ];

                if ($prefs['comments_inline_annotator_with_info'] === 'y') {
                    $annotation['commentDate'] = $tikilib->get_short_datetime($comment['commentDate']);
                    $annotation['user']        = $comment['userName'];
                    $annotation['realName']    = $userlib->clean_user($comment['userName']);
                }

                $comments[] = $annotation;
            }
        };

        return [
            'total' => count($comments),    // TODO pagination
            'rows' => $comments,
        ];
    }

    /**
     * @param $text
     * @return string
     * @throws Exception
     * @throws SmartyException
     */
    private function createTitle($text)
    {
        $smarty = TikiLib::lib('smarty');
        return smarty_modifier_truncate($text, 50);
    }

    /**
     * @param $quote
     * @param $text
     * @return string
     */
    private function formatComment($quote, $text)
    {
        $safeQuote = str_replace(["\n", "\r"], ' ', $quote);
        $safeQuote = addslashes($safeQuote);
        return ';note:' . $safeQuote . "\n\n" . $text;
    }
}
