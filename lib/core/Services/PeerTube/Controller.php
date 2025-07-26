<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Services_PeerTube_Controller
{
    public function setUp()
    {
        Services_Exception_Disabled::check('feature_peertube');
    }

    /**
     * @param $input JitFilter
     *              sort_mode string   default desc_createdAt
     *              find string        search query for video title or description
     *              maxRecords int     entries per page
     *              offset int         for paging
     *              formId string      id of the form to add the video to
     *              targetName string  name of the target hidden input
     *
     * @return array
     * @throws Exception
     * @throws Services_Exception_Denied
     */
    public function action_list($input)
    {
        $perms = Perms::get();
        if (! $perms->list_peertube) {
            throw new Services_Exception_Denied('Not allowed to list videos');
        }

        $sort_mode = $input->sort_mode->word() ?: 'desc_createdAt';
        $find = $input->find->text();
        $page_size = $input->maxRecords->int() ?: -1;
        $offset = max(0, $input->offset->int());
        $page = ($offset / $page_size) + 1;

        $peertubelib = TikiLib::lib('peertubeuser');
        $videoList = $peertubelib->listVideos($sort_mode, $page, $page_size, $find);

        $out = [
            'entries' => $videoList->objects,
            'totalCount' => $videoList->totalCount,
            'formId' => $input->formId->text(),
            'targetName' => $input->targetName->text(),
        ];

        return $out;
    }
}
