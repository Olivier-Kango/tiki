<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Tiki tracker modules
 * @package modules
 * @subpackage tiki
 */

if (!defined('DEBUG_MODE')) { die(); }

/**
 * Prepare message page title
 * @subpackage tiki/handler
 */
class Hm_Handler_tracker_message_list_type extends Hm_Handler_Module {
    public function process() {
        $path = $this->request->get['list_path'];
        if (!strstr($path, 'tracker_folder_')) {
            return;
        }
        $title = ['Tracker Folder'];
        $trackerId = str_replace('tracker_', '', $this->request->get['list_parent']);
        $path = str_replace('tracker_folder_', '', $this->request->get['list_path']);
        list ($itemId, $fieldId) = explode('_', $path);
        $definition = Tracker_Definition::get($trackerId);
        if ($definition) {
            $title[] = $definition->getConfiguration('name');
            $field = $definition->getField($fieldId);
            if ($field) {
                $title[] = $field['name'];
            }
            if ($itemId) {
                $title[] = TikiLib::lib('trk')->get_isMain_value($trackerId, $itemId);
            }
        }
        $this->out('mailbox_list_title', $title);
    }
}

/**
 * Check for tracker item link redirect
 * @subpackage tiki/handler
 */
class Hm_Handler_check_path_redirect extends Hm_Handler_Module {
    public function process() {
        global $smarty;
        $smarty->loadPlugin('smarty_modifier_sefurl');
        $path = $this->request->get['list_path'];
        if (preg_match("/tracker_folder_(\d+)_(\d+)/", $this->request->get['list_path'], $m)) {
            $url = smarty_modifier_sefurl($m[1], 'trackeritem');
            Hm_Dispatch::page_redirect($url);
        }
    }
}

/**
 * Move an email to a tracker Email Folder field
 * @subpackage tiki/handler
 */
class Hm_Handler_move_to_tracker extends Hm_Handler_Module {
    public function process() {
        global $smarty;

        list($success, $form) = $this->process_form(array('tracker_field_id', 'tracker_item_id', 'imap_msg_uid', 'imap_server_id', 'folder'));
        if (! $success) {
            return;
        }
        $cache = Hm_IMAP_List::get_cache($this->cache, $form['imap_server_id']);
        $imap = Hm_IMAP_List::connect($form['imap_server_id'], $cache);
        if (! imap_authed($imap)) {
            Hm_Msgs::add('ERRCould not authenticate with mail server');
            return;
        }
        if (! $imap->select_mailbox(hex2bin($form['folder']))) {
            Hm_Msgs::add('ERRMailbox not found');
            return;
        }
        $msg = $imap->get_message_content($form['imap_msg_uid'], 0);
        $msg = str_replace("\r\n", "\n", $msg);
        $msg = str_replace("\n", "\r\n", $msg);
        $msg = rtrim($msg)."\r\n";

        $headers = $imap->get_message_headers($form['imap_msg_uid']);

        // ensure file was saved before removing it from remote mailbox
        TikiLib::events()->bind('tiki.trackeritem.update', function($args) {
            $imap = $args['imap'];
            $form = $args['form'];
            $old = $args['old_values'][$form['tracker_field_id']];
            $new = $args['values'][$form['tracker_field_id']];
            if (substr_count($old, ',') != substr_count($new, ',')) {
                if ($imap->message_action('DELETE', array($form['imap_msg_uid']))) {
                   $imap->message_action('EXPUNGE', array($form['imap_msg_uid']));
                }
            }
        }, ['imap' => $imap, 'form' => $form]);

        $trk = TikiLib::lib('trk');
        $item = $trk->get_item_info($form['tracker_item_id']);

        if (! $item) {
            Hm_Msgs::add('ERRTracker item not found');
            return;
        }

        $field = $trk->get_field_info($form['tracker_field_id']);
        if (! $field) {
            Hm_Msgs::add('ERRTracker field not found');
            return;
        }

        $field['value'] = [
            'existing' => $trk->get_item_value($item['trackerId'], $item['itemId'], $form['tracker_field_id']),
            'name' => !empty($headers['Message-ID']) ? $headers['Message-ID'] : $headers['Subject'],
            'size' => strlen($msg),
            'type' => 'message/rfc822',
            'content' => $msg
        ];

        $trk->replace_item($item['trackerId'], $item['itemId'], [
            'data' => [$field]
        ]);

        Hm_Msgs::add('Message moved');

        $smarty->loadPlugin('smarty_modifier_sefurl');
        $url = smarty_modifier_sefurl($item['itemId'], 'trackeritem');
        $this->out('redirect_url', $url);
    }
}

/**
 * Get message content from Tiki tracker EmailField storage and prepare for imap module display
 * @subpackage tiki/handler
 */
class Hm_Handler_tiki_message_content extends Hm_Handler_Module {
    public function process() {
        list($success, $form) = $this->process_form(array('imap_msg_uid'));
        if (! $success) {
            return;
        }

        $this->out('msg_text_uid', $form['imap_msg_uid']);
        $this->out('msg_list_path', $this->request->post['list_path']);
        $part = false;
        if (isset($this->request->post['imap_msg_part']) && preg_match("/[0-9\.]+/", $this->request->post['imap_msg_part'])) {
            $part = $this->request->post['imap_msg_part'];
        }
        if (array_key_exists('imap_allow_images', $this->request->post) && $this->request->post['imap_allow_images']) {
            $this->out('imap_allow_images', true);
        }
        $this->out('header_allow_images', $this->config->get('allow_external_image_sources'));

        $trk = TikiLib::lib('trk');
        $path = str_replace('tracker_folder_', '', $this->request->post['list_path']);
        list ($itemId, $fieldId) = explode('_', $path);

        $field = $trk->get_field_info($fieldId);
        if (! $field) {
            Hm_Msgs::add('ERRTracker field not found');
            return;
        }

        $item = $trk->get_item_info($itemId);
        if (! $item) {
            Hm_Msgs::add('ERRTracker item not found');
            return;
        }
        $item[$field['fieldId']] = $trk->get_item_value(null, $item['itemId'], $field['fieldId']);

        $definition = Tracker_Definition::get($field['trackerId']);
        $handler = $trk->get_field_handler($field, $item);
        $data = $handler->getFieldData();

        if (! isset($data['emails']) || ! is_array($data['emails'])) {
            Hm_Msgs::add('ERRTracker field storage is broken or you are using the wrong field type');
            return;
        }

        $email = false;
        foreach ($data['emails'] as $eml) {
            if ($eml['fileId'] == $form['imap_msg_uid']) {
                $email = $eml;
                break;
            }
        }

        if (! $email) {
            Hm_Msgs::add('ERREmail not found in related tracker item');
            return;
        }

        if (empty($email['message_raw'])) {
            Hm_Msgs::add('ERREmail could not be parsed');
            return;
        }

        $message = $email['message_raw'];

        $msg_headers = [];
        foreach ($message->getAllHeaders() as $header) {
            $msg_headers[$header->getName()] = $header->getRawValue();
        }
        $msg_struct = tiki_mime_part_to_bodystructure($message);
        $msg_struct_current = [];

        if ($part !== false) {
            // TODO
        } else {
            if (!$this->user_config->get('text_only_setting', false)) {
                $part = $message->getHtmlPart();
                if (! $part) {
                    $part = $message->getTextPart();
                }
                // TODO: $msg_text = add_attached_images($msg_text, $form['imap_msg_uid'], $msg_struct, $imap);
            } else {
                $part = $message->getTextPart();
            }
            $msg_text = $part->getContent();
            $list = explode('/', $part->getContentType());
            $msg_struct_current = [
                'type' => $list[0],
                'subtype' => $list[1],
            ];
        }

        $this->out('msg_struct', $msg_struct);
        $this->out('list_headers', get_list_headers($msg_headers));
        $this->out('msg_headers', $msg_headers);
        $this->out('imap_msg_part', "$part");
        $this->out('use_message_part_icons', $this->user_config->get('msg_part_icons_setting', false));
        $this->out('simple_msg_part_view', $this->user_config->get('simple_msg_parts_setting', false));
        if ($msg_struct_current) {
            $this->out('msg_struct_current', $msg_struct_current);
        }
        $this->out('msg_text', $msg_text);
        $this->out('msg_download_args', sprintf("page=message&amp;uid=%s&amp;list_path=amp;imap_download_message=1", $form['imap_msg_uid'], $this->request->post['list_path']));
        $this->out('msg_show_args', sprintf("page=message&amp;uid=%s&amp;list_path=&amp;imap_show_message=1", $form['imap_msg_uid'], $this->request->post['list_path']));

        clear_existing_reply_details($this->session);
        if ($part == 0) {
            $msg_struct_current['type'] = 'text';
            $msg_struct_current['subtype'] = 'plain';
        }
        $this->session->set(sprintf('reply_details__%s', $this->request->post['list_path'], $form['imap_msg_uid']),
            array('ts' => time(), 'msg_struct' => $msg_struct_current, 'msg_text' => $msg_text, 'msg_headers' => $msg_headers));
    }
}

/**
 * Add Move to trackers button
 * @subpackage tiki/output
 */
class Hm_Output_add_move_to_trackers extends Hm_Output_Module {
    protected function output() {
        $trk = TikiLib::lib('trk');
        $fields = $trk->get_fields_by_type('EF');
        if (! $fields) {
            return;
        }
        $field_list = [];
        foreach ($fields as $field) {
            $tracker = $trk->get_tracker($field['trackerId']);
            $field_list[] = "<a href='#' class='object_selector_trigger' data-tracker='{$field['trackerId']}' data-field='{$field['fieldId']}'>{$tracker['name']} - {$field['name']}</a>";
        }
        $res = "| <a class=\"hlink\" id=\"move_to_trackers\" href=\"#\">".$this->trans('Trackers')."</a>";
        $res .= "<div class='move_to_trackers'><div class='move_to_title'>Move to trackers...<span><a class='close_move_to_trackers' href='#'>X</a></span></div>".implode("<br>\n", $field_list)."</div>";
        $headers = $this->get('msg_headers');
        $headers = preg_replace("#<a class=\"archive_link[^>]*>.*?</a>#", "\\0 ".$res, $headers);
        $this->out('msg_headers', $headers, false);
    }
}

/**
 * Pass redirect_url param to the output
 * @subpackage tiki/output
 */
class Hm_Output_pass_redirect_url extends Hm_Output_Module {
    protected function output() {
        $url = $this->get('redirect_url');
        $this->out('redirect_url', $url);
    }
}
