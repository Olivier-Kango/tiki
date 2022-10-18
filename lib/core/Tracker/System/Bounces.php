<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\System;

use Exception;
use TikiDb;
use TikiLib;

class Bounces
{
    private $trklib;
    private $trackerId;
    private $fields;

    public function __construct()
    {
        global $prefs;

        $this->trklib = TikiLib::lib('trk');

        if (empty($prefs['tracker_system_bounces']) || $prefs['tracker_system_bounces'] !== 'y') {
            throw new Exception(tr('Bounces system tracker not allowed.'));
        }

        if (empty($prefs['tracker_system_bounces_tracker']) || empty($prefs['tracker_system_bounces_mailbox'])) {
            throw new Exception(tr('Bounces system tracker not configured with required fields.'));
        }

        $this->trackerId = $prefs['tracker_system_bounces_tracker'];

        $this->fields = [
            'mailbox' => $prefs['tracker_system_bounces_mailbox'],
            'emailfolder' => $prefs['tracker_system_bounces_emailfolder'],
            'soft_total' => $prefs['tracker_system_bounces_soft_total'],
            'hard_total' => $prefs['tracker_system_bounces_hard_total'],
            'blacklisted' => $prefs['tracker_system_bounces_blacklisted'],
        ];
    }

    public function insert($mailbox, $headers, $msg, $type)
    {
        $item = $this->get_by_mailbox($mailbox);
        $fields = [];
        $field = $this->trklib->get_field_info($this->fields['mailbox']);
        $field['value'] = $mailbox;
        $fields[] = $field;
        if ($type == 'soft' && $this->fields['soft_total']) {
            $field = $this->trklib->get_field_info($this->fields['soft_total']);
            $field['value'] = intval(@$item[$this->fields['soft_total']]) + 1;
            $fields[] = $field;
        }
        if ($type == 'hard' && $this->fields['hard_total']) {
            $field = $this->trklib->get_field_info($this->fields['hard_total']);
            $field['value'] = intval(@$item[$this->fields['hard_total']]) + 1;
            $fields[] = $field;
        }
        if ($this->fields['emailfolder']) {
            $field = $this->trklib->get_field_info($this->fields['emailfolder']);
            $field['value'] = [
                'new' => [
                    'name' => ! empty($headers['Message-Id']) ? $headers['Message-Id'] : $headers['Subject'],
                    'size' => strlen($msg),
                    'type' => 'message/rfc822',
                    'content' => $msg
                ]
            ];
            $fields[] = $field;
        }
        return $this->trklib->replace_item($this->trackerId, @$item['itemId'], [
            'data' => $fields
        ]);
    }

    public function get_by_mailbox($mailbox)
    {
        return $this->trklib->get_item($this->trackerId, $this->fields['mailbox'], $mailbox);
    }
}
