<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE.

namespace Services\Peertube;

class ServicesPeerTubeController
{
    public function setUp()
    {
        \Services_Exception_Disabled::check('feature_peertube');
    }

    public function action_list($input)
    {
        $perms = \Perms::get();
        if (! $perms->list_videos) {
            throw new \Services_Exception_Denied(tr('Not allowed to list videos'));
        }

        global $prefs;

        $sort_mode = $input->sort_mode->word() ?: 'created_desc';

        $map = [
            'created_desc'   => '-createdAt',
            'created_asc'    => 'createdAt',
            'published_desc' => '-publishedAt',
            'published_asc'  => 'publishedAt',
            'name_desc'      => '-name',
            'name_asc'       => 'name',

            'desc_createdAt'    => '-createdAt',
            'asc_createdAt'     => 'createdAt',
            'desc_publishedAt'  => '-publishedAt',
            'asc_publishedAt'   => 'publishedAt',
        ];
        $apiSort = $map[$sort_mode] ?? '-publishedAt';

        $page_size = (int) $input->maxRecords->int();
        if ($page_size <= 0) {
            $page_size = (int) ($prefs['maxRecords'] ?? 20);
        }
        $page_size = max(1, min(100, $page_size));

        $offset = max(0, (int) $input->offset->int());
        $page   = (int) floor($offset / $page_size) + 1;

        $find = $input->find->text();

        $peertube = \TikiLib::lib('peertubeuser');
        $list = $peertube->listVideos($apiSort, $page, $page_size, $find);

        return [
            'entries'    => $list->data ?? [],
            'totalCount' => $list->total ?? 0,
            'formId'     => $input->formId->text(),
            'targetName' => $input->targetName->text(),
        ];
    }

    public function action_upload($input)
    {
        $perms = \Perms::get();
        if (! $perms->upload_peertube) {
            throw new \Services_Exception_Denied(tr('Not allowed to upload videos'));
        }

        return [
            'ok'         => true,
            'formId'     => $input->formId->text(),
            'targetName' => $input->targetName->text(),
        ];
    }
}

class_alias(\Services\Peertube\ServicesPeerTubeController::class, 'Services_Peertube_Controller');
