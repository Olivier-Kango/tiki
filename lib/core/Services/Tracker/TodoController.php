<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Tracker_TodoController
{
    public function setUp()
    {
        global $prefs;

        if ($prefs['feature_trackers'] != 'y') {
            throw new Services_Exception_Disabled('feature_trackers');
        }

        if (! Perms::get()->admin_trackers) {
            throw new Services_Exception(tr('Operation reserved for tracker administrators'), 403);
        }
    }

    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'trackers';
    }

    public function action_view($input)
    {
        $trackerId = $input->trackerId->int();
        $definition = Tracker_Definition::get($trackerId);

        if (! $definition) {
            throw new Services_Exception_NotFound();
        }

        $todolib = TikiLib::lib('todo');
        $trklib = TikiLib::lib('trk');

        return [
            'title' => tr('Events'),
            'trackerId' => $trackerId,
            'todos' => $todolib->listTodoObject('tracker', $trackerId),
            'statusTypes' => $definition->getStatusTypes(),
        ];
    }

    public function action_add($input)
    {
        $trackerId = $input->trackerId->int();
        $definition = Tracker_Definition::get($trackerId);

        if (! $definition) {
            throw new Services_Exception_NotFound();
        }

        $delayAfter = abs($input->after->int() * $input->after_unit->int());
        $delayNotif = abs($input->notif->int() * $input->notif_unit->int());
        $from = $input->from->word();
        $to = $input->to->word();
        $event = $input->event->word();
        $subject = $input->subject->text();
        $body = $input->body->text();

        $todolib = TikiLib::lib('todo');

        if (! $delayAfter) {
            throw new Services_Exception_MissingValue('after');
        }

        $todoId = $todolib->addTodo(
            $delayAfter,
            $event,
            'tracker',
            $trackerId,
            ['status' => $from],
            ['status' => $to]
        );

        if ($delayNotif) {
            $detail = [
                'mail' => 'creator',
                'before' => $delayNotif,
            ];

            if ($subject) {
                $detail['subject'] = $subject;
            }

            if ($body) {
                $detail['body'] = $body;
            }

            $todolib->addTodo($delayAfter - $delayNotif, $event, 'todo', $todoId, "", $detail);
        }

        return [
            'trackerId' => $trackerId,
            'todoId' => $todoId,
        ];
    }

    public function action_delete($input)
    {
        TikiLib::lib('todo')->delTodo($input->todoId->int());
        return [
            'FORWARD' => [
                'controller' => 'tracker_todo',
                'action' => 'view',
                'trackerId' => $input->trackerId->int(),
            ],
        ];
    }
}
