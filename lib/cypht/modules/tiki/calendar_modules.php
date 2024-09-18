<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Tiki calendar modules
 * @package modules
 * @subpackage tiki
 */

if (! defined('DEBUG_MODE')) {
    die();
}

require_once APP_PATH . 'modules/smtp/hm-mime-message.php';

/**
 * Parse message and check for a calendar invitation
 * @subpackage tiki/handler
 */
class Hm_Handler_check_calendar_invitations_imap extends Hm_Handler_Module
{
    public function process()
    {
        if ($this->get('msg_struct')) {
            get_calendar_part_imap($this->get('msg_struct'), $this);
        }
        if ($this->get('calendar_event_raw')) {
            $data = Tiki\SabreDav\Utilities::getDenormalizedData($this->get('calendar_event_raw'));
            $this->out('calendar_event', $data);
        }
        $recipient = null;
        $headers = $this->get('msg_headers', []);
        foreach ($headers as $name => $value) {
            if (strtolower($name) == 'to') {
                $recipient = (string)$value;
            }
        }
        $this->out('recipient', $recipient);
    }
}

/**
 * Send a RSVP for an event
 * @subpackage tiki/handler
 */
class Hm_Handler_event_rsvp_action extends Hm_Handler_Module
{
    public $output;
    public $protected;
    public $config;
    public function process()
    {
        list($success, $form) = $this->process_form(['rsvp_action']);
        if (! $success) {
            return;
        }

        $recipient = $this->get('recipient');

        $calendardata = $this->get('calendar_event_raw');
        if (! $calendardata) {
            return;
        }

        // format answer
        $partstat = null;
        $action = "";
        switch ($form['rsvp_action']) {
            case 'accept':
                $partstat = 'ACCEPTED';
                $action = 'accepted';
                break;
            case 'maybe':
                $partstat = 'TENTATIVE';
                $action = 'tentatively accepted';
                break;
            case 'decline':
                $partstat = 'DECLINED';
                $action = 'declined';
                break;
        }

        // parse event and format response
        $vObject = Sabre\VObject\Reader::read($calendardata);
        $vObject->method  = 'REPLY';
        foreach ($vObject->getComponents() as $component) {
            if ($component->name !== 'VEVENT') {
                continue;
            }
            if (isset($component->ATTENDEE)) {
                foreach ($component->ATTENDEE as $attendee) {
                    $email = preg_replace("/MAILTO:\s*/i", "", (string)$attendee);
                    if ($email === $recipient && $partstat) {
                        $attendee['PARTSTAT'] = $partstat;
                        unset($attendee['RSVP']);
                        $component->ATTENDEE = $attendee;
                    }
                }
            }
            $component->DTSTAMP = \DateTime::createFromFormat('U', time())->format('Ymd\THis\Z');
        }
        $event_response = $vObject->serialize();
        $vObject->destroy();

        // format reply, smtp server details and recipients
        list($to, $cc, $subject, $body, $in_reply_to) = format_reply_fields(
            $this->get('msg_text'),
            $this->get('msg_headers'),
            $this->get('msg_struct_current'),
            false,
            new Hm_Output_add_rsvp_actions($this->output, $this->protected),
            'reply'
        );

        // use specific text body for the reply
        $event = $this->get('calendar_event');
        $body = "$from_name has $action the invitation to the following event:

*{$event['name']}*

When: " . TikiLib::lib('tiki')->get_long_datetime($event['start']) . " - " . TikiLib::lib('tiki')->get_long_datetime($event['end']) . "

Invitees: " . implode(",\n", $event['attendees']);

        // add attachments
        $content = Hm_Crypt::ciphertext($event_response, Hm_Request_Key::generate());
        $filename = hash('sha512', $content);
        $filepath = rtrim($this->config->get('attachment_dir'), '/');
        if (@file_put_contents($filepath . '/' . $filename, $content)) {
            $file = [
                'filename' => $filepath . '/' . $filename,
                'basename' => $filename,
                'type' => 'text/calendar; method=REPLY',
                'name' => 'event.ics',
                'no_encoding' => true,
            ];
        } else {
            $file = null;
        }

        $profiles = $this->get('compose_profiles', []);
        $recip = get_primary_recipient($profiles, $this->get('msg_headers'), $this->get('smtp_servers', []));

        $result = tiki_send_email_through_cypht($to, $cc, $subject, $body, $in_reply_to, $file, $profiles, $this, $recip);

        if (! empty($file['filename'])) {
            @unlink($file['filename']);
        }

        if (! $result) {
            return;
        }

        // sync partstat for local calendar event
        global $prefs, $user;
        if ($prefs['feature_calendar'] === 'y') {
            $existing = TikiLib::lib('calendar')->find_by_uid(null, $event['uid']);
            if ($existing) {
                $event['calendarId'] = $existing['calendarId'];
                $event['calitemId'] = $existing['calitemId'];
                if (! empty($event['participants'])) {
                    foreach ($event['participants'] as &$role) {
                        if ($role['email'] === $recipient && $partstat) {
                            $role['partstat'] = $partstat;
                        }
                    }
                }
                $client = new \Tiki\SabreDav\CaldavClient();
                if ($existing['recurrenceId']) {
                    $rec = new CalRecurrence($existing['recurrenceId']);
                    if ($event['rec']) {
                        $event['rec']->setId($rec->getId());
                        $event['rec']->setUri($rec->getUri());
                        $rec = $event['rec'];
                    }
                    $rec->setUser($user);
                    $client->saveRecurringCalendarObject($rec);
                } else {
                    $client->saveCalendarObject($event);
                }
            }
        }
    }
}

/**
 * Add an event to Tiki calendar
 * @subpackage tiki/handler
 */
class Hm_Handler_add_to_calendar extends Hm_Handler_Module
{
    public function process()
    {
        global $prefs, $user;

        if ($prefs['feature_calendar'] !== 'y') {
            return;
        }

        list($success, $form) = $this->process_form(['calendar_id']);
        if (! $success) {
            Hm_Msgs::add("ERRNo calendar selected");
            return;
        }

        $calendar = TikiLib::lib('calendar')->get_calendar($form['calendar_id']);
        if (! $calendar) {
            Hm_Msgs::add("ERRSelected calendar is unavailable");
            return;
        }

        $perms = Perms::get('calendar', $form['calendar_id']);
        if (! $perms->add_events) {
            Hm_Msgs::add("ERRInsufficient permissions to create the event in the selected calendar");
            return;
        }

        $data = $this->get('calendar_event');
        $data['calendarId'] = $form['calendar_id'];
        $data['user'] = $user;

        $client = new \Tiki\SabreDav\CaldavClient();
        if ($data['rec']) {
            if (empty($data['priority'])) {
                $data['priority'] = 0;
            }
            if (is_null($data['status'])) {
                $data['status'] = 1;
            }
            if (empty($data['lang'])) {
                $data['lang'] = 'en';
            }
            if (empty($data['nlId'])) {
                $data['nlId'] = 0;
            }
            $rec = $data['rec'];
            $client->saveRecurringCalendarObject($rec);
        } else {
            $client->saveCalendarObject($data);
        }

        Hm_Msgs::add("Event created");
    }
}

/**
 * Update participant status for a Tiki calendar event
 * @subpackage tiki/handler
 */
class Hm_Handler_update_participant_status extends Hm_Handler_Module
{
    public function process()
    {
        global $prefs;

        if ($prefs['feature_calendar'] !== 'y') {
            return;
        }

        $event = $this->get('calendar_event');
        $from = null;
        $headers = $this->get('msg_headers', []);
        foreach ($headers as $name => $value) {
            if (strtolower($name) == 'from') {
                $from = (string)$value;
            }
        }

        $existing = TikiLib::lib('calendar')->find_by_uid(null, $event['uid']);
        if ($existing) {
            if (! empty($event['participants'])) {
                foreach ($event['participants'] as &$role) {
                    if ($role['email'] === $from) {
                        TikiLib::lib('calendar')->update_partstat($existing['calitemId'], $role['username'], $role['partstat']);
                    }
                }
            }
        }
        Hm_Msgs::add("Information updated");
    }
}

/**
 * Remove an event from Tiki calendar when cancelation email is received
 * @subpackage tiki/handler
 */
class Hm_Handler_remove_from_calendar extends Hm_Handler_Module
{
    public function process()
    {
        global $prefs, $user;

        if ($prefs['feature_calendar'] !== 'y') {
            return;
        }

        $event = $this->get('calendar_event');
        $existing = TikiLib::lib('calendar')->find_by_uid(null, $event['uid']);
        if ($existing) {
            $client = new \Tiki\SabreDav\CaldavClient();
            $client->deleteCalendarObject($existing);
        }

        Hm_Msgs::add("Event removed");
    }
}

/**
 * Show RSVP buttons if message contains a calendar invitation
 * @subpackage tiki/output
 */
class Hm_Output_add_rsvp_actions extends Hm_Output_Module
{
    protected function output()
    {
        global $prefs, $user;
        $method = $this->get('calendar_method');
        $event = $this->get('calendar_event');
        $headers = $this->get('msg_headers');
        if (! empty($event)) {
            $res = '';
            $res .= sprintf('<tr class="header_event_dtstart"><th>%s</th><td>%s</td></tr>', tr('Event start'), TikiLib::lib('tiki')->get_long_datetime($event['start']));
            $res .= sprintf('<tr class="header_event_dtend"><th>%s</th><td>%s</td></tr>', tr('Event end'), TikiLib::lib('tiki')->get_long_datetime($event['end']));
            $res .= sprintf('<tr class="header_event_organizer"><th>%s</th><td>%s</td></tr>', tr('Organizer'), implode(", ", $event['real_organizers']));
            if ($prefs['feature_calendar'] == 'y' && $method != 'CANCEL') {
                $existing = TikiLib::lib('calendar')->find_by_uid(null, $event['uid']);
                if (! $existing) {
                    $options = ['<option></option>'];
                    $calendars = TikiLib::lib('calendar')->list_calendars();
                    $calendars['data'] = Perms::filter([ 'type' => 'calendar' ], 'object', $calendars['data'], [ 'object' => 'calendarId' ], 'add_events');
                    foreach ($calendars['data'] as $row) {
                        $options[] = "<option value='" . $row['calendarId'] . "'>" . $row['name'] . "</option>";
                    }
                    $res .= sprintf(
                        '<tr class="header_event_addtocal"><th>%s</th><td class="header_links"><select name="calendarId" class="event_calendar_select">%s</select></td></tr>',
                        tr('Add to calendar'),
                        implode('', $options)
                    );
                }
            }
            if ($method == 'REQUEST') {
                $partstat = null;
                $existing = TikiLib::lib('calendar')->find_by_uid(null, $event['uid']);
                if ($existing) {
                    $existing = TikiLib::lib('calendar')->get_item($existing['calitemId']);
                }
                if ($existing && ! empty($existing['participants'])) {
                    foreach ($existing['participants'] as $role) {
                        if ($role['email'] == $this->get('recipient')) {
                            $partstat = $role['partstat'];
                        }
                    }
                }
                $yes_tag = $maybe_tag = $no_tag = "a";
                if ($partstat == 'ACCEPTED') {
                    $yes_tag = "span";
                }
                if ($partstat == 'TENTATIVE') {
                    $maybe_tag = "span";
                }
                if ($partstat == 'DECLINED') {
                    $no_tag = "span";
                }
                $res .= sprintf(
                    '<tr class="header_event_rsvp"><th>%s</th><td class="header_links"><%s class="event_rsvp_link hlink" data-action="accept" href="#">%s</%s> | <%s class="event_rsvp_link hlink" data-action="maybe" href="#">%s</%s> | <%s class="event_rsvp_link hlink" data-action="decline" href="#">%s</%s></td></tr>',
                    tr('RSVP'),
                    $yes_tag,
                    tr('Yes'),
                    $yes_tag,
                    $maybe_tag,
                    tr('Maybe'),
                    $maybe_tag,
                    $no_tag,
                    tr('No'),
                    $no_tag
                );
            }
            if ($prefs['feature_calendar'] == 'y' && $method == 'REPLY') {
                $existing = TikiLib::lib('calendar')->find_by_uid(null, $event['uid']);

                if ($existing) {
                    $participants = TikiLib::lib('calendar')->get_participant_by_event_uid($event['uid']);
                    $participant_status_updated = true;

                    if (! empty($existing['participants'])) {
                        foreach ($event['participants'] as $role) {
                            $idx = array_search($role['username'], array_column($participants, 'username'));
                            if (! $idx) {
                                $idx = array_search($role['email'], array_column($participants, 'username'));
                            }

                            if ($idx && $role['partstat'] != $participants[$idx]['partstat']) {
                                $participant_status_updated = false;
                                break;
                            }
                        }
                    }

                    $event_update_participant_class = 'event_update_participant_status';
                    $event_update_participant_text = 'Update participant status';

                    if ($participant_status_updated) {
                        $event_update_participant_class = 'event_participant_status_updated';
                        $event_update_participant_text = 'Participant status updated';
                    }


                    $res .= sprintf(
                        '<tr>
                            <th colspan="2" class="header_links">
                            <a href="#" class="' . $event_update_participant_class . '">%s</a>
                            </th>
                        </tr>',
                        tr($event_update_participant_text)
                    );
                }
            }
            if ($prefs['feature_calendar'] == 'y' && $method == 'CANCEL') {
                $existing = TikiLib::lib('calendar')->find_by_uid(null, $event['uid']);
                if ($existing) {
                    $res .= sprintf(
                        '<tr><th colspan="2" class="header_links"><a href="#" class="event_remove_from_calendar">%s</a></th></tr>',
                        tr('Remove from calendar')
                    );
                }
            }
            $headers = preg_replace("#<tr><td[^>]*header_space[^>]*>.*?</td></tr>#", $res . "\\0", $headers);
        }
        $this->out('msg_headers', $headers, false);
    }
}

/**
 * Search imap message structure for text/calendar parts
 * @subpackage tiki/functions
 * @param array $struct message structure
 * @param object $mod Hm_Handler_Module
 * @return string
 */
if (! hm_exists('get_calendar_part_imap')) {
    function get_calendar_part_imap($struct, $mod)
    {
        $event = $method = null;
        $part = false;
        foreach ($struct as $id => $vals) {
            if (is_array($vals) && isset($vals['type'])) {
                if ($vals['type'] . '/' . $vals['subtype'] == 'text/calendar') {
                    $part = $id;
                    $method = $vals['attributes']['method'];
                }
                if (isset($vals['subs'])) {
                    return get_calendar_part_imap($vals['subs'], $mod);
                }
            } else {
                if (is_array($vals) && count($vals) == 1 && isset($vals['subs'])) {
                    return get_calendar_part_imap($vals['subs'], $mod);
                }
            }
        }
        if (! $part) {
            return;
        }
        list($success, $form) = $mod->process_form(['imap_server_id', 'imap_msg_uid', 'folder']);
        if ($success) {
            $cache = Hm_IMAP_List::get_cache($mod->cache, $form['imap_server_id']);
            $imap = Hm_IMAP_List::connect($form['imap_server_id'], $cache);
            if (imap_authed($imap)) {
                if ($imap->select_mailbox(hex2bin($form['folder']))) {
                    $msg_struct = $imap->get_message_structure($form['imap_msg_uid']);
                    if ($part !== false) {
                        if ($part == 0) {
                            $max = 500000;
                        } else {
                            $max = false;
                        }
                        $struct = $imap->search_bodystructure($msg_struct, ['imap_part_number' => $part]);
                        $msg_struct_current = array_shift($struct);
                        $event = $imap->get_message_content($form['imap_msg_uid'], $part, $max, $msg_struct_current);
                    }
                }
            }
        }
        $mod->out('calendar_method', $method);
        $mod->out('calendar_event_raw', $event);
    }
}
