<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_GlobalSource_CommentSource implements Search_GlobalSource_Interface
{
    private $commentslib;
    private $table;

    public function __construct()
    {
        $this->commentslib = TikiLib::lib('comments');
        $this->table = TikiDb::get()->table('tiki_comments');
    }

    public function getProvidedFields(): array
    {
        return [
            'comment_count',
            'comment_data',
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'comment_count' => 'numeric',
            'comment_data' => 'plaintext',
        ];
    }

    public function getGlobalFields(): array
    {
        return [
            'comment_data' => false,
        ];
    }

    public function getData($objectType, $objectId, Search_Type_Factory_Interface $typeFactory, array $data = [])
    {
        $data = '';
        if ($objectType == 'forum post') {
            $forumId = $this->commentslib->get_comment_forum_id($objectId);
            $comment_count = $this->commentslib->count_comments_threads("forum:$forumId", $objectId);
        } else {
            $comment_count = $this->commentslib->count_comments("$objectType:$objectId");

            $data = implode(' ', $this->table->fetchColumn('data', [
                'object' => $objectId,
                'objectType' => $objectType,
            ]));

            // this gets appended to the global contents source, so we need memory at least double the length of the string
            $available = TikiLib::lib('tiki')->get_memory_avail();
            if ($available > 0 && strlen($data) > $available) {
                $data = substr($data, 0, floor((strlen($data) + $available - 10 * 1024 * 1024) / 2);
            }
        }
        return [
            'comment_count' => $typeFactory->numeric($comment_count),
            'comment_data' => $typeFactory->plaintext($data),
        ];
    }
}
