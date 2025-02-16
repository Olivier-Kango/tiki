<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (! defined('ROLE_ORGANIZER')) {
    define('ROLE_ORGANIZER', '6');
}

/**
 *
 */
class CalendarLib extends TikiLib
{
    /**
     * @param $sort_mode
     * @return string
     */
    public function convertSortMode($sort_mode, $fields = null)
    {
        $tmp = explode("_", $sort_mode);
        if (count($tmp) == 2) {
            if ($tmp[0] == "categoryName" || $tmp[0] == "locationName") {
                return "name " . $tmp[1];
            }
        }
        return parent::convertSortMode($sort_mode, $fields);
    }

    /**
     * @param int $offset
     * @param $maxRecords
     * @param string $sort_mode
     * @param string $find
     * @param string $user
     * @return mixed
     */
    public function list_calendars($offset = 0, $maxRecords = -1, $sort_mode = 'name_asc', $find = '', $fuser = '', $filter_admin_calendar = false)
    {
        global $user;

        $mid = '';
        $res = [];
        $bindvars = [];
        $join = '';

        if ($find) {
            $mid = "and tcal.`name` like ?";
            $bindvars[] = '%' . $find . '%';
        }

        if ($fuser) {
            $mid = "and ( tcal.`user` = ? or tcali.`calendarInstanceId` IS NOT NULL )";
            $bindvars[] = $fuser;
            $join .= ' left join `tiki_calendar_instances` tcali on tcal.calendarId = tcali.calendarId and tcali.`user` = ?';
            array_unshift($bindvars, $fuser);
        }

        if ($filter_admin_calendar && ! Perms::get()->admin_calendar) {
            if (Perms::get()->admin_private_calendar) {
                $mid .= " AND tcal.`private` = 'y' AND tcal.`user` = ?";
                $bindvars[] = $user;
            } else {
                $mid .= ' AND 0';
            }
        }

        $categlib = TikiLib::lib('categ');

        if ($jail = $categlib->get_jail()) {
            $categlib->getSqlJoin($jail, 'calendar', 'tcal.`calendarId`', $join, $mid, $bindvars);
        }

        $sort = $this->convertSortMode($sort_mode, ['calendarId', 'name', 'customlocations', 'customparticipants', 'customlanguages', 'personal', 'private']);
        if (! empty($sort) && $sort != '1') {
            $sort = 'tcal.' . $sort;
        }

        $query = "select tcal.* from `tiki_calendars` as tcal $join where 1=1 $mid order by " . $sort;
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $query_cant = "select count(*) from `tiki_calendars` as tcal $join where 1=1 $mid";
        $cant = $this->getOne($query_cant, $bindvars);

        $res = [];
        while ($r = $result->fetchRow()) {
            $k = $r["calendarId"];
            if (! empty($r['name'])) {
                $r['displayName'] = $r['name'];
            } else {
                $r['displayName'] = tr("Id #%0", $r['calendarId']);
            }
            $res2 = $this->query("select `optionName`,`value` from `tiki_calendar_options` where `calendarId`=?", [(int)$k]);
            while ($r2 = $res2->fetchRow()) {
                if ($r2['optionName'] == 'eventstatus') {
                    $eventstatus = json_decode($r2['value']);
                    $r[$r2['optionName']] = $eventstatus;
                    //add translation tag to statuses for display
                    $r["eventstatusoutput"] = array_map(
                        function ($status) {
                            return tra($status);
                        },
                        $eventstatus
                    );
                } else {
                    $r[$r2['optionName']] = $r2['value'];
                }
            }
            if ($fuser) {
                // override with per user instance values if those exist
                $query = "select * from `tiki_calendar_instances` where calendarId = ? and user = ?";
                $instance_result = $this->query($query, [$r['calendarId'], $fuser]);
                $instance = $instance_result->fetchRow();
                if ($instance) {
                    $r['name'] = $instance['name'];
                    $r['description'] = $instance['description'];
                    $r['order'] = $instance['order'];
                    $r['color'] = $instance['color'];
                    $r['timezone'] = $instance['timezone'];
                    $r['access'] = $instance['access'];
                    $r['calendarInstanceId'] = $instance['calendarInstanceId'];
                }
            }
            if (! isset($r['calendarInstanceId'])) {
                $r['calendarInstanceId'] = 0;
            }
            $r['name'] = tra($r['name']);
            $res["$k"] = $r;
        }
        $retval["data"] = $res;
        $retval["cant"] = $cant;
        return $retval;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function get_calendarId_from_name($name)
    {
        $query = 'select `calendarId` from `tiki_calendars` where `name`=?';
        return $this->getOne($query, [$name]);
    }

    /**
     * @param $calendarId
     * @param $user
     * @param $name
     * @param $description
     * @param array $customflags
     * @param array $options
     * @param $instanceId
     * @return mixed
     */
    public function set_calendar($calendarId, $user, $name, $description, $customflags = [], $options = [], $instanceId = 0)
    {
        global $prefs;
        $name = strip_tags($name);
        $description = strip_tags($description);
        $now = time();
        if ($instanceId > 0) {
            // modification of a calendar instance
            $finalEvent = 'tiki.calendar.update';
            $query = "update `tiki_calendar_instances` set `name`=?, `description`=?, `transparent`=?, `timezone`=?, `order`=?, `color`=? where `calendarInstanceId`=?";
            $bindvars = [$name,$description, @$options['transparent'], @$options['timezone'], @$options['order'], @$options['custombgcolor'], $instanceId];
            $result = $this->query($query, $bindvars);
        } elseif ($calendarId > 0) {
            // modification of a calendar
            $finalEvent = 'tiki.calendar.update';
            $query = "update `tiki_calendars` set `name`=?, `user`=?, `description`=?, ";
            $bindvars = [$name,$user,$description];
            foreach ($customflags as $k => $v) {
                $query .= "`$k`=?, ";
                $bindvars[] = $v;
            }
            $query .= "`lastmodif`=?  where `calendarId`=?";
            $bindvars[] = $now;
            $bindvars[] = $calendarId;
            $result = $this->query($query, $bindvars);
            // merge existing options in case passed array does not contain the full list (e.g. caldav integration)
            $res = $this->query("select `optionName`,`value` from `tiki_calendar_options` where `calendarId`=?", [(int)$calendarId]);
            while ($r = $res->fetchRow()) {
                if (! isset($options[$r['optionName']])) {
                    $options[$r['optionName']] = $r['optionName'] == 'viewdays' ? unserialize($r['value']) : $r['value'];
                }
            }
        } else {
            // create a new calendar
            $finalEvent = 'tiki.calendar.create';
            $query = 'insert into `tiki_calendars` (`name`,`user`,`description`,`created`,`lastmodif`';
            $bindvars = [$name,$user,$description,$now,$now];
            if (! empty($customflags)) {
                $query .= ',`' . implode("`,`", array_keys($customflags)) . '`';
            }
            $query .= ') values (?,?,?,?,?';
            if (! empty($customflags)) {
                $query .= ',' . implode(",", array_fill(0, count($customflags), "?"));
                foreach ($customflags as $k => $v) {
                    $bindvars[] = $v;
                }
            }
            $query .= ')';
            $result = $this->query($query, $bindvars);
            $calendarId = $this->GetOne("select `calendarId` from `tiki_calendars` where `created`=?", [$now]);
        }
        if ($instanceId == 0) {
            $this->query('delete from `tiki_calendar_options` where `calendarId`=?', [(int)$calendarId]);
            if (count($options)) {
                if (isset($options['viewdays'])) {
                    $options['viewdays'] = serialize($options['viewdays']);
                } else {
                    $options['viewdays'] = serialize($prefs['calendar_view_days']);
                }
                if (isset($options['eventstatus'])) {
                    $options['eventstatus'] = json_encode($options['eventstatus']);
                }
                foreach ($options as $name => $value) {
                    $name = preg_replace('/[^-_a-zA-Z0-9]/', '', $name);
                    $this->query('insert into `tiki_calendar_options` (`calendarId`,`optionName`,`value`) values (?,?,?)', [(int)$calendarId,$name,$value]);
                }
            }
        }

        TikiLib::events()->trigger($finalEvent, [
            'type' => 'calendar',
            'object' => $calendarId,
            'user' => $GLOBALS['user'],
        ]);

        return $calendarId;
    }

    /**
     * @param $calendarId
     * @return array
     */
    public function get_calendar($calendarId)
    {
        global $prefs;
        if (substr($calendarId, 0, 1) === 's') {
            $subscription = $this->get_subscription(substr($calendarId, 1));
            $subscription['startday'] = ! empty($prefs['calendar_start_day']) ? $prefs['calendar_start_day'] : 25200;
            $subscription['endday'] = ! empty($prefs['calendar_end_day']) ? $prefs['calendar_end_day'] : 72000;
            $subscription['viewdays'] = $prefs['calendar_view_days'];
            return $subscription;
        }
        $res = $this->query("select * from `tiki_calendars` where `calendarId`=?", [(int)$calendarId]);
        $cal = $res->fetchRow();
        $res2 = $this->query("select `optionName`,`value` from `tiki_calendar_options` where `calendarId`=?", [(int)$calendarId]);
        while ($r = $res2->fetchRow()) {
            $cal[$r['optionName']] = $r['value'];
        }
        if (! isset($cal['startday']) and ! isset($cal['endday'])) {
            $cal['startday'] = 0;
            $cal['endday'] = 23 * 60 * 60;
        }
        if (isset($cal['viewdays'])) {
            $cal['viewdays'] = unserialize($cal['viewdays']);
        } else {
            $cal['viewdays'] = $prefs['calendar_view_days'];
        }

        if (isset($cal['eventstatus'])) {
            $cal['eventstatus'] = json_decode($cal['eventstatus']);
            //add translation tag to statuses for display
            $cal["eventstatusoutput"] = array_map(
                function ($status) {
                    return tra($status);
                },
                $cal["eventstatus"]
            );
        }
        $cal = array_merge(['allday' => 'n', 'nameoneachday' => 'n', 'copybuttononeachevent' => 'n'], $cal);
        return $cal;
    }

    /**
     * @param $calendarId
     * @return bool
     */
    public function calendarExists($calendarId)
    {
        $res = $this->query("select `calendarId` from `tiki_calendars` where `calendarId`=?", [(int)$calendarId]);
        $cal = $res->fetchRow();

        return empty($cal) ? false : true;
    }

    public function get_calendar_options($calendarId)
    {
        $opts = [];
        $res = $this->query("select `optionName`,`value` from `tiki_calendar_options` where `calendarId`=?", [(int)$calendarId]);
        while ($r = $res->fetchRow()) {
            $opts[$r['optionName']] = $r['value'];
        }
        return $opts;
    }

    /**
     * @param $calitemId
     * @return mixed
     */
    public function get_calendarid($calitemId)
    {
        return $this->getOne("select `calendarId` from `tiki_calendar_items` where `calitemId`=?", [(int)$calitemId]);
    }

    /**
     * @param $calendarId
     */
    public function drop_calendar($calendarId)
    {
        $transaction = $this->begin();

        // find and remove roles for all calendar items:
        $query = "select `calitemId` from `tiki_calendar_items` where `calendarId`=?";
        $result = $this->query($query, [ $calendarId ]);
        $allItemsFromCalendar = [];
        while ($res = $result->fetchRow()) {
            $allItemsFromCalendar[] = $res['calitemId'];
        }
        if (count($allItemsFromCalendar) > 0) {
            $query = "delete from `tiki_calendar_roles` where `calitemId` in (" . implode(',', array_fill(0, count($allItemsFromCalendar), '?')) . ")";
            $this->query($query, $allItemsFromCalendar);
        }
        // remove calendar items, categories and locations:
        $query = "delete from `tiki_calendar_items` where `calendarId`=?";
        $this->query($query, [$calendarId]);
        $query = "delete from `tiki_calendar_categories` where `calendarId`=?";
        $this->query($query, [$calendarId]);
        $query = "delete from `tiki_calendar_options` where `calendarId`=?";
        $this->query($query, [$calendarId]);
        $query = "delete from `tiki_calendar_locations` where `calendarId`=?";
        $this->query($query, [$calendarId]);
        $query = "delete from `tiki_calendar_instances` where `calendarId`=?";
        $this->query($query, [$calendarId]);
        $query = "delete from `tiki_calendar_changes` where `calendarId`=?";
        $this->query($query, [$calendarId]);
        // uncategorize calendar
        $categlib = TikiLib::lib('categ');
        $categlib->uncategorize_object('calendar', $calendarId);
        // now remove the calendar itself:
        $query = "delete from `tiki_calendars` where `calendarId`=?";
        $dropResult = $this->query($query, [$calendarId]);

        TikiLib::events()->trigger('tiki.calendar.delete', [
            'type' => 'calendar',
            'object' => $calendarId,
            'user' => $GLOBALS['user'],
        ]);

        $transaction->commit();
        return $dropResult;
    }

    /* tsart ans tstop are in user time - the data base is in server time */
    /**
     * @param $calIds
     * @param $user
     * @param $tstart
     * @param $tstop
     * @param $offset
     * @param $maxRecords
     * @param string $sort_mode
     * @param string $find
     * @param array $customs
     * @return array
     */
    public function list_raw_items($calIds, $user, $tstart, $tstop, $offset, $maxRecords, $sort_mode = 'start_asc', $find = '', $customs = [])
    {
        global $prefs;

        if (count($calIds) == 0) {
            return [];
        }

        $calIdsCanAdmin = Perms::filter(
            ['type' => 'calendar'],
            'object',
            array_map(function ($calId) {
                return ['calendarId' => $calId];
            }, $calIds),
            [ 'object' => 'calendarId' ],
            'admin_calendar'
        );
        $calIdsCanAdmin = array_map(function ($object) {
            return intval($object['calendarId']);
        }, $calIdsCanAdmin);
        if (empty($calIdsCanAdmin)) {
            $calIdsCanAdmin[] = 0;
        }

        $where = [];
        $bindvars = [];
        foreach ($calIds as $calendarId) {
            $where[] = "i.`calendarId`=?";
            $bindvars[] = (int)$calendarId;
        }

        $cond = "(" . implode(" or ", $where) . ") and ";
        $cond .= " ((i.`start` > ? and i.`end` < ?) or (i.`start` < ? and i.`end` > ?))";

        $bindvars[] = (int)$tstart;
        $bindvars[] = (int)$tstop;
        $bindvars[] = (int)$tstop;
        $bindvars[] = (int)$tstart;

        $cond .= " and (c.`personal` != 'y' or (c.`personal`='y' and c.`private` = 'n' and i.`user`=?) or (c.`personal` = 'y' and c.`private` = 'y' and (i.`user` = ? or c.calendarId in (" . implode(', ', $calIdsCanAdmin) . "))))";
        $bindvars[] = $user;
        $bindvars[] = $user;

        $query = "select i.`calitemId` as `calitemId` ";
        $queryCompl = '';
        $joinCompl = '';
        $tblRef = 'i.';

        if (substr($sort_mode, 0, 12) == "categoryName") {
            $queryCompl = "`tiki_calendar_categories` as compl right join ";
            $joinCompl = " on i.categoryId = compl.calcatid ";
            $tblRef = "compl.";
        } elseif (substr($sort_mode, 0, 12) == "locationName") {
            $queryCompl = "`tiki_calendar_locations` as compl right join ";
            $joinCompl = " on i.locationId = compl.callocid ";
            $tblRef = "compl.";
        }

        $query .= 'from ' . $queryCompl . '`tiki_calendar_items` as i ' . $joinCompl .
                                " left join `tiki_calendars` as c on i.`calendarId`=c.`calendarId`" .
                                " where ($cond)" .
                                " order by " .
                                $tblRef . $this->convertSortMode($sort_mode) . ',i.' . $this->convertSortMode('calendarId_asc');

        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $ret = [];
        while ($res = $result->fetchRow()) {
            $ret[] = $this->get_item($res["calitemId"], $customs);
        }

        foreach ($calIds as $calendarId) {
            if (substr($calendarId, 0, 1) === 's') {
                $parserlib = TikiLib::lib('parser');
                $timezone = TikiLib::lib('tiki')->get_display_timezone();
                $sub = $this->get_subscription(substr($calendarId, 1));
                if (empty($sub['vcalendar'])) {
                    continue;
                }
                $vcalendar = Sabre\VObject\Reader::read($sub['vcalendar']);
                $expanded = $vcalendar->expand(DateTime::createFromFormat('U', $tstart), DateTime::createFromFormat('U', $tstop), new DateTimeZone($timezone));
                foreach ($expanded->VEVENT as $component) {
                    $parsed = Tiki\SabreDav\Utilities::getDenormalizedDataFromComponent($component);
                    $ret[] = [
                        'calitemId' => 0,
                        'calendarId' => $calendarId,
                        'user' => $sub['user'],
                        'start' => $parsed['start'],
                        'end' => $parsed['end'],
                        'calname' => $sub['name'],
                        'locationId' => intval($parsed['locationId'] ?? 0),
                        'locationName' => '',
                        'categoryId' => intval($parsed['categoryId'] ?? 0),
                        'categoryName' => '',
                        'priority' => strval($parsed['priority'] ?? 0),
                        'nlId' => 0,
                        'uid' => $parsed['uid'],
                        'uri' => '',
                        'status' => $parsed['status'] ?? 1,
                        'url' => strval($parsed['url'] ?? ''),
                        'lang' => strval($parsed['lang'] ?? ''),
                        'name' => strval($parsed['name'] ?? ''),
                        'description' => strval($parsed['description'] ?? ''),
                        'created' => $parsed['created'] ?? 0,
                        'lastModif' => $parsed['lastmodif'] ?? 0,
                        'allday' => $parsed['allday'] ?? 0,
                        'customlocations' => 'n',
                        'customcategories' => 'n',
                        'customlanguages' => 'n',
                        'custompriorities' => 'n',
                        'customsubscription' => 'n',
                        'customparticipants' => 'n',
                        'recurrenceStart' => null,
                        'recurrenceUid' => null,
                        'participants' => $parsed['participants'] ?? [],
                        'selected_participants' => isset($parsed['participants']) ? array_map(function ($role) {
                            return $role['username'];
                        }, $parsed['participants']) : [],
                        'organizers' => $parsed['organizers'] ?? [],
                        'date_start' => (int)$parsed['start'],
                        'date_end' => (int)$parsed['end'],
                        'duration' => $parsed['end'] - $parsed['start'],
                        'parsed' => $parserlib->parse_data(
                            $parsed['description'] ?? '',
                            ['is_html' => $prefs['calendar_description_is_html'] === 'y']
                        ),
                        'parsedName' => $parserlib->parse_data($parsed['name'] ?? ''),
                    ];
                }
            }
        }

        return $ret;
    }

    /**
     * @param $calIds
     * @param $user
     * @param $tstart
     * @param $tstop
     * @param $offset
     * @param $maxRecords
     * @param string $sort_mode
     * @param string $find
     * @param array $customs
     * @return array
     */
    public function list_items($calIds, $user, $tstart, $tstop, $offset, $maxRecords, $sort_mode = 'start_asc', $find = '', $customs = [])
    {
        global $tiki_p_change_events, $prefs;
        $ret = [];
        $list = $this->list_raw_items($calIds, $user, $tstart, $tstop, $offset, $maxRecords, $sort_mode, $find, $customs);
        foreach ($list as $res) {
            $mloop = TikiLib::date_format("%m", $res['start']);
            $dloop = TikiLib::date_format("%d", $res['start']);
            $yloop = TikiLib::date_format("%Y", $res['start']);
            $dstart = TikiLib::make_time(0, 0, 0, $mloop, $dloop, $yloop);
            $dend = TikiLib::make_time(0, 0, 0, TikiLib::date_format("%m", $res['end']), TikiLib::date_format("%d", $res['end']), TikiLib::date_format("%Y", $res['end']));
            $tstart = TikiLib::date_format("%H%M", $res["start"]);
            $tend = TikiLib::date_format("%H%M", $res["end"]);
            for ($i = $dstart; $i <= $dend; $i = TikiLib::make_time(0, 0, 0, $mloop, ++$dloop, $yloop)) {
                /* $head is in user time */
                if ($dstart == $dend) {
                    $head = TikiLib::date_format($prefs['short_time_format'], $res["start"]) . " - " . TikiLib::date_format($prefs['short_time_format'], $res["end"]);
                } elseif ($i == $dstart) {
                    $head = TikiLib::date_format($prefs['short_time_format'], $res["start"]) . " ...";
                } elseif ($i == $dend) {
                    $head = " ... " . TikiLib::date_format($prefs['short_time_format'], $res["end"]);
                } else {
                    $head = " ... " . tra("continued") . " ... ";
                }

                /* $i is timestamp unix of the beginning of a day */
                $ret["$i"][] = [
                    'result' => $res,
                    'calitemId' => $res['calitemId'],
                    'calname' => tra($res['calname']),
                    'time' => $tstart, /* user time */
                    'end' => $tend, /* user time */
                    'type' => $res['status'],
                    'web' => $res['url'],
                    'startTimeStamp' => $res['start'],
                    'endTimeStamp' => $res['end'],
                    'nl' => $res['nlId'],
                    'prio' => $res['priority'],
                    'location' => $res['locationName'],
                    'category' => $res['categoryName'],
                    'name' => $res['name'],
                    'head' => $head,
                    'parsedDescription' => TikiLib::lib('parser')->parse_data($res['description'], ['is_html' => $prefs['calendar_description_is_html'] === 'y', 'objectType' => 'calendar event', 'objectId' => $res['calitemId'], 'fieldName' => 'description']),
                    'description' => str_replace("\n|\r", '', $res['description']),
                    'calendarId' => $res['calendarId'],
                    'status' => $res['status'],
                    'user' => $res['user']
                ];
            }
        }
        return $ret;
    }

    /**
     * @param $calIds
     * @param $user
     * @param $tstart
     * @param $tstop
     * @param $offset
     * @param $maxRecords
     * @param string $sort_mode
     * @param string $find
     * @param array $customs
     * @return array
     */
    public function list_items_by_day($calIds, $user, $tstart, $tstop, $offset, $maxRecords, $sort_mode = 'start_asc', $find = '', $customs = [])
    {
        global $prefs;
        $ret = [];
        $list = $this->list_raw_items($calIds, $user, $tstart, $tstop, $offset, $maxRecords, $sort_mode, $find, $customs);
        foreach ($list as $res) {
            $mloop = TikiLib::date_format("%m", $res['start']);
            $dloop = TikiLib::date_format("%d", $res['start']);
            $yloop = TikiLib::date_format("%Y", $res['start']);
            $dstart = TikiLib::make_time(0, 0, 0, $mloop, $dloop, $yloop);
            $dend = TikiLib::make_time(0, 0, 0, TikiLib::date_format("%m", $res['end']), TikiLib::date_format("%d", $res['end']), TikiLib::date_format("%Y", $res['end']));
            $tstart = TikiLib::date_format("%H%M", $res["start"]);
            $tend = TikiLib::date_format("%H%M", $res["end"]);
            for ($i = $dstart; $i <= $dend; $i = TikiLib::make_time(0, 0, 0, $mloop, ++$dloop, $yloop)) {
                /* $head is in user time */
                if ($res['allday'] == '1') {
                    $head = tra('All day');
                } elseif ($dstart == $dend) {
                    $head = TikiLib::date_format($prefs['short_time_format'], $res["start"]) . " - " . TikiLib::date_format($prefs['short_time_format'], $res["end"]);
                } elseif ($i == $dstart) {
                    $head = TikiLib::date_format($prefs['short_time_format'], $res["start"]) . " ...";
                } elseif ($i == $dend) {
                    $head = " ... " . TikiLib::date_format($prefs['short_time_format'], $res["end"]);
                } else {
                    $head = " ... " . tra("continued") . " ... ";
                }

                /* $i is timestamp unix of the beginning of a day */
                $j = (isset($ret[$i]) && is_array($ret[$i])) ? count($ret[$i]) : 0;

                $ret[$i][$j] = $res;
                $ret[$i][$j]['head'] = $head;
                $ret[$i][$j]['parsedDescription'] = TikiLib::lib('parser')->parse_data($res["description"], ['is_html' => $prefs['calendar_description_is_html'] === 'y', 'objectType' => 'calendar event', 'objectId' => $res['calitemId'], 'fieldName' => 'description']);
                $ret[$i][$j]['description'] = str_replace("\n|\r", "", $res["description"]);
                $ret[$i][$j]['visible'] = 'y';
                $ret[$i][$j]['where'] = $res['locationName'];

                $ret[$i][$j]['show_description'] = 'y';
                /*  'time' => $tstart, /* user time */
                /*  'end' => $tend, /* user time */

                $ret[$i][$j]['group_description'] = htmlspecialchars($res['name']) . '<span class="calgrouptime">, ' . $head . '</span>';
            }
        }
        return $ret;
    }

    /**
     * @param $calitemId
     * @param array $customs
     * @return mixed
     */
    public function get_item($calitemId, $customs = [])
    {
        global $user, $prefs;

        $query = "select i.`calitemId` as `calitemId`, i.`calendarId` as `calendarId`, i.`user` as `user`, i.`start` as `start`, i.`end` as `end`, t.`name` as `calname`, ";
        $query .= "i.`locationId` as `locationId`, l.`name` as `locationName`, i.`categoryId` as `categoryId`, c.`name` as `categoryName`, c.`backgroundColor` as `categoryBackgroundColor`, i.`priority` as `priority`, i.`nlId` as `nlId`, i.`uid` as `uid`, i.`uri` as `uri`, ";
        $query .= "i.`status` as `status`, i.`url` as `url`, i.`lang` as `lang`, i.`name` as `name`, i.`description` as `description`, i.`created` as `created`, i.`lastmodif` as `lastModif`, i.`allday` as `allday`, ";
        $query .= "t.`customlocations`, t.`customcategories`, t.`customlanguages`, t.`custompriorities`, t.`customsubscription`, t.`customparticipants`, i.`recurrenceId`, i.`recurrenceStart`, r.`uid` as `recurrenceUid`";

        foreach ($customs as $k => $v) {
            $query .= ", i.`$k` as `$v`";
        }

        $query .= " from `tiki_calendar_items` as i left join `tiki_calendar_locations` as l on i.`locationId`=l.`callocId` left join `tiki_calendar_recurrence` as r on i.`recurrenceId` = r.`recurrenceId`";
        $query .= " left join `tiki_calendar_categories` as c on i.`categoryId`=c.`calcatId` left join `tiki_calendars` as t on i.`calendarId`=t.`calendarId` where `calitemId`=?";
        $result = $this->query($query, [(int)$calitemId]);
        $res = $result->fetchRow();

        if ($res) {
            $query
                = "select `username`, `role`, `partstat` from `tiki_calendar_roles` where `calitemId`=? order by `role`";
            $rezult = $this->query($query, [(int)$calitemId]);
            $ppl = [];
            $org = [];
            while ($rez = $rezult->fetchRow()) {
                if ($rez["role"] == ROLE_ORGANIZER) {
                    $org[] = $rez["username"];
                } elseif ($rez["username"]) {
                    $email = TikiLib::lib('user')->get_user_email($rez['username']);
                    if (! $email) {
                        $email = $rez["username"];
                    }
                    $ppl[] = [
                        'username' => $rez["username"],
                        'email' => $email,
                        'role' => $rez["role"],
                        'partstat' => $rez['partstat']
                    ];
                }
            }
            $res["participants"] = $ppl;
            $res["selected_participants"] = array_map(function ($role) {
                return $role['username'];
            }, $ppl);
            $res["organizers"] = $org;
            $res['date_start'] = (int)$res['start'];
            $res['date_end'] = (int)$res['end'];
            $res['duration'] = $res['end'] - $res['start'];
            $parserlib = TikiLib::lib('parser');
            $res['parsed'] = $parserlib->parse_data(
                $res['description'],
                [
                    'is_html' => $prefs['calendar_description_is_html'] === 'y',
                    'objectType' => 'calendar event',
                    'objectId' => $res['calitemId'],
                    'fieldName' => 'description'
                ]
            );
            $res['parsedName'] = $parserlib->parse_data($res['name']);
        }
        return $res;
    }

    /**
     * @param $calitemId
     * @return array
     */
    public function getItemWithRecurrence($calitemId)
    {
        $events = [];
        $query = "select `calitemId` from `tiki_calendar_items` where `recurrenceId`=? or `calitemId`=?";
        $result = $this->query($query, [(int)($this->get_item($calitemId)['recurrenceId'] ?? 0), $calitemId]);
        while ($res = $result->fetchRow()) {
            $events[] = $this->get_item($res['calitemId']);
        }
        return $events;
    }

    public function get_item_by_uri($uri)
    {
        $result = $this->query("select calitemId from `tiki_calendar_items` where uri = ?", [$uri]);
        $row = $result->fetchRow();
        if ($row) {
            return $this->get_item($row['calitemId']);
        }
        $result = $this->query("select recurrenceId from `tiki_calendar_recurrence` where uri = ?", [$uri]);
        $row = $result->fetchRow();
        if ($row) {
            return new \CalRecurrence($row['recurrenceId']);
        }
        return null;
    }

    public function getMaxItemId()
    {
        $result = $this->query("select MAX(calitemId) as maxId from tiki_calendar_items");
        $row = $result->fetchRow();
        return $row['maxId'];
    }

    /**
     * @param       $user
     * @param       $calitemId
     * @param       $data
     * @param array $customs
     * @param bool  $isBulk
     *
     * @return bool
     * @throws Exception
     */
    public function set_item($user, $calitemId, $data, $customs = [], $isBulk = false)
    {
        global $prefs;
        if (! isset($data['calendarId'])) {
            return false;
        }
        $caldata = $this->get_calendar($data['calendarId']);

        if ($caldata['customlocations'] == 'y') {
            if (! $data["locationId"] and ! $data["newloc"]) {
                $data['locationId'] = 0;
            }
            if (trim($data["newloc"])) {
                $bindvars = [(int)$data["calendarId"],trim($data["newloc"])];
                $data["locationId"] = $this->getOne("select `callocId` from `tiki_calendar_locations` where `calendarId`=? and `name`=?", $bindvars);
                if (empty($data["locationId"])) {
                    $query = "insert into `tiki_calendar_locations` (`calendarId`,`name`) values (?,?)";
                    $this->query($query, $bindvars);
                    $data["locationId"] = $this->getOne("select `callocId` from `tiki_calendar_locations` where `calendarId`=? and `name`=?", $bindvars);
                }
            }
        } else {
            $data['locationId'] = 0;
        }

        if ($caldata['customcategories'] == 'y') {
            if (! $data["categoryId"] and ! $data["newcat"]) {
                $data['categoryId'] = 0;
            }
            if (trim($data["newcat"])) {
                $calendarId = (int)$data["calendarId"];
                $newcatname = trim($data["newcat"]);
                $newcatbgcolor = trim($data["newcatbgcolor"]);

                $data["categoryId"] = $this->getOne("select `calcatId` from `tiki_calendar_categories` where `calendarId`=? and `name`=?", [$calendarId, $newcatname]);
                if (empty($data["categoryId"])) {
                    $query = "insert into `tiki_calendar_categories` (`calendarId`,`name`,`backgroundColor`) values (?,?,?)";
                    $this->query($query, [$calendarId, $newcatname, $newcatbgcolor]);
                    $data["categoryId"] = $this->getOne("select `calcatId` from `tiki_calendar_categories` where `calendarId`=? and `name`=?", [$calendarId, $newcatname]);
                }
            }
        } else {
            $data['categoryId'] = 0;
        }

        $roles = [];
        if ($caldata['customparticipants'] == 'y') {
            if ($data["organizers"]) {
                if (is_string($data['organizers'])) {
                    $data['organizers'] = preg_split('/\s*,\s*/', $data['organizers']);
                }
                foreach ($data['organizers'] as $o) {
                    if (trim($o)) {
                        $roles[] = [
                            'username' => trim($o),
                            'role' => ROLE_ORGANIZER
                        ];
                    }
                }
            }
            if ($data["participants"]) {
                foreach ($data['participants'] as $pa) {
                    if (trim($pa['username'])) {
                        $roles[] = $pa;
                    }
                }
            }
        }

        if ($caldata['customlanguages'] == 'y') {
            if (! isset($data['lang'])) {
                $data['lang'] = '';
            }
        } else {
            $data['lang'] = '';
        }

        if ($caldata['custompriorities'] == 'y') {
            if (! isset($data['priority'])) {
                $data['priority'] = 0;
            }
        } else {
            $data['priority'] = 0;
        }

        if ($caldata['customsubscription'] == 'y') {
            if (! isset($data['nlId'])) {
                $data['nlId'] = 0;
            }
        } else {
            $data['nlId'] = 0;
        }

        $data['user'] = $user;

        $realcolumns = ['calitemId', 'calendarId', 'start', 'end', 'locationId', 'categoryId', 'nlId', 'priority', 'uri', 'uid',
                     'status', 'url', 'lang', 'name', 'description', 'user', 'created', 'lastmodif', 'allday', 'recurrenceId', 'changed', 'recurrenceStart'];
        foreach ($customs as $custom) {
            $realcolumns[] = $custom;
        }

        if ($calitemId) {
            $finalEvent = 'tiki.calendaritem.update';

            $oldData = $this->get_item($calitemId);
            if (empty($oldData)) {
                return false;
            }
            $data = array_merge($oldData, $data);
            $data['lastmodif'] = $this->now;

            $l = [];
            $r = [];

            foreach ($data as $k => $v) {
                if (! in_array($k, $realcolumns)) {
                    continue;
                }
                $l[] = "`$k`=?";
                $r[] = $v;
            }

            if (! empty($data['changed']) && empty($data['recurrenceStart'])) {
                $l[] = "`recurrenceStart` = ?";
                $r[] = $oldData['start'];
            }

            if (! empty($data['recurrenceStart']) && empty($data['changed'])) {
                $l[] = "`changed` = 1";
            }

            $query = 'UPDATE `tiki_calendar_items` SET ' . implode(',', $l) . ' WHERE `calitemId`=?';
            $r[] = (int)$calitemId;

            $result = $this->query($query, $r);
            $this->add_change($data['calendarId'], $calitemId, 2);

            $trackerItemsIds = $this->getAttachedTrackerItems($calitemId);

            require_once 'lib/search/refresh-functions.php';
            foreach ($trackerItemsIds as $trackerItemId) {
                refresh_index('trackeritem', $trackerItemId);
            }
        } else {
            $finalEvent = 'tiki.calendaritem.create';
            $new = true;
            $oldData = null;
            $data['lastmodif'] = $this->now;
            $data['created'] = $this->now;

            $l = [];
            $r = [];
            $z = [];

            foreach ($data as $k => $v) {
                if (! in_array($k, $realcolumns)) {
                    continue;
                }
                $l[] = "`$k`";
                $z[] = '?';
                if ($k == 'priority') {
                    $v = (string)$v;
                }
                $r[] = $v;
            }

            $query = 'INSERT INTO `tiki_calendar_items` (' . implode(',', $l) . ') VALUES (' . implode(',', $z) . ')';
            $result = $this->query($query, $r);
            $calitemId = $this->GetOne("SELECT MAX(`calitemId`) FROM `tiki_calendar_items` where `calendarId`=?", [$data["calendarId"]]);
            $this->add_change($data['calendarId'], $calitemId, 1);
        }

        if ($calitemId) {
            $wikilib = TikiLib::lib('wiki');
            $wikilib->update_wikicontent_relations($data['description'], 'calendar event', $calitemId);
            $wikilib->update_wikicontent_links($data['description'], 'calendar event', $calitemId);
            $existing_roles = $this->fetchAll('select * from `tiki_calendar_roles` where `calitemId`=?', [$calitemId]);
            $query = "delete from `tiki_calendar_roles` where `calitemId`=?";
            $this->query($query, [(int)$calitemId]);
        } else {
            $existing_roles = [];
        }

        if ($prefs['feature_categories'] == 'y') {
            $catCategories = isset($_REQUEST['cat_categories']) ? $_REQUEST['cat_categories'] : [];
            $catCatManaged = isset($_REQUEST['cat_managed']) ? $_REQUEST['cat_managed'] : [];
            $this->update_item_categories($calitemId, $catCatManaged, $catCategories, $data['name'], $data['description']);
        }

        if ($roles !== null) {
            foreach ($roles as $role) {
                if (empty($role['partstat'])) {
                    foreach ($existing_roles as $erole) {
                        if ($role['username'] == $erole['username']) {
                            $role['partstat'] = $erole['partstat'];
                        }
                    }
                }
                $query = "insert into `tiki_calendar_roles` (`calitemId`,`username`,`role`,`partstat`) values (?,?,?,?)";
                $this->query($query, [(int)$calitemId, $role['username'], $role['role'] ?? 0, $role['partstat'] ?? null]);
            }
        }

        if ($prefs['feature_user_watches'] == 'y') {
            $this->watch($calitemId, $data);
        }

        TikiLib::events()->trigger($finalEvent, [
            'type' => 'calendaritem',
            'object' => $calitemId,
            'user' => $GLOBALS['user'],
            'bulk_import' => $isBulk,
            'old_data' => $oldData,
            'process_itip' => ! empty($data['process_itip'])
        ]);

        return $calitemId;
    }

    /**
     * Get all tracker items attached to a calender item
     *
     * @param $calitemId
     *
     * @return array
     * @throws Exception
     */
    public function getAttachedTrackerItems($calitemId)
    {
        $trackerItems = [];
        $attributes = TikiLib::lib('attribute')->find_objects_with('tiki.calendar.item', $calitemId);

        foreach ($attributes as $attribute) {
            $trackerItems[] = (int)$attribute['itemId'];
        }

        return $trackerItems;
    }

    /**
     * @param $calitemId
     * @param $data
     */
    public function watch($calitemId, $data)
    {
        global $prefs, $user;
        $smarty = TikiLib::lib('smarty');
        $tikilib = TikiLib::lib('tiki');
        $nots = $tikilib->get_event_watches('calendar_changed', $data['calendarId']);

        if ($prefs['calendar_watch_editor'] != "y" || $prefs['user_calendar_watch_editor'] != "y") {
            for ($i = count($nots) - 1; $i >= 0; --$i) {
                if ($nots[$i]['user'] == $data["user"]) {
                    unset($nots[$i]);
                    break;
                }
            }
        }

        if ($prefs['feature_daily_report_watches'] == 'y') {
            $reportsManager = Reports_Factory::build('Reports_Manager');
            $reportsManager->addToCache($nots, ['event' => 'calendar_changed', 'calitemId' => $calitemId, 'user' => $user]);
        }

        if ($nots) {
            include_once('lib/webmail/tikimaillib.php');
            $mail = new TikiMail();
            $smarty->assign('mail_new', $new);
            $smarty->assign('mail_data', $data);
            $smarty->assign('view_item_url', TikiLib::lib('service')->getUrl([
                'controller' => 'calendar',
                'action' => 'view_item',
                'calitemId' => $calitemId,
                'calIds' => [$data['calendarId']],
            ]));
            $defaultLanguage = $prefs['site_language'];
            foreach ($nots as $not) {
                $mail->setUser($not['user']);
                $mail_data = $smarty->fetchLang($defaultLanguage, "mail/user_watch_calendar_subject.tpl");
                $mail->setSubject($mail_data);
                $mail_data = $smarty->fetchLang($defaultLanguage, "mail/user_watch_calendar.tpl");
                $mail->setText($mail_data);
                $mail->send([$not['email']]);
            }
        }
    }

    /**
     * @param $user
     * @param $calitemId
     */
    public function drop_item($user, $calitemId, $isBulk = false, $process_itip = true)
    {
        if ($calitemId) {
            $item = $this->get_item($calitemId);
            $query = "delete from `tiki_calendar_items` where `calitemId`=?";
            $this->query($query, [$calitemId]);
            $query = "delete from `tiki_calendar_roles` where `calitemId`=?";
            $this->query($query, [$calitemId]);
            $this->remove_object('calendar event', $calitemId);
            TikiLib::lib('calendar')->add_change($item['calendarId'], $calitemId, 3);

            TikiLib::events()->trigger('tiki.calendaritem.delete', [
                'type' => 'calendaritem',
                'object' => $calitemId,
                'user' => $user,
                'bulk_import' => $isBulk,
                'old_data' => $item,
                'process_itip' => $process_itip
            ]);
        }
    }

    /**
     * @param $calitemId
     * @param int $delay
     */
    public function move_item($calitemId, $delay = 0)
    {
        $delay = $this->get_delay($delay);
        if ($delay != 0) {
            $query = 'UPDATE `tiki_calendar_items` set start = start + ?, end = end + ? WHERE `calitemId`=?';
            $this->query($query, [$delay,$delay,$calitemId]);
        }
    }

    /**
     * @param $calitemId
     * @param int $delay
     */
    public function resize_item($calitemId, $delay = 0)
    {
        $delay = $this->get_delay($delay);
        if ($delay != 0) {
            $query = 'UPDATE `tiki_calendar_items` set end = end + ? WHERE `calitemId`=?';
            $this->query($query, [$delay,$calitemId]);
        }
    }

    /**
     * @param $calendarId
     * @return array
     */
    public function list_locations($calendarId)
    {
        $res = [];
        if ($calendarId > 0) {
            $query = "select `callocId` as `locationId`, `name` from `tiki_calendar_locations` where `calendarId`=? order by `name`";
            return $this->fetchAll($query, [$calendarId]);
        }
        return $res;
    }

    /**
     * @param $calendarId
     * @return array
     */
    public function list_categories($calendarId)
    {
        $res = [];
        if ($calendarId > 0) {
            $query = "select `calcatId` as `categoryId`, `name` from `tiki_calendar_categories` where `calendarId`=? order by `name`";
            return $this->fetchAll($query, [$calendarId]);
        }
        return $res;
    }

    // Returns the last $maxrows of modified events for an
    // optional $calendarId
    /**
     * @param $maxrows
     * @param int $calendarId
     * @return mixed
     */
    public function last_modif_events($maxrows = -1, $calendarId = 0)
    {

        if ($calendarId > 0) {
            $cond = "where `calendarId` = ? ";
            $bindvars = [$calendarId];
        } else {
            $cond = '';
            $bindvars = [];
        }

        $query = "select `start`, `name`, `calitemId`, `calendarId`, `user`, `lastModif` from `tiki_calendar_items` " . $cond . "order by " . $this->convertSortMode('lastModif_desc');

        return $this->fetchAll($query, $bindvars, $maxrows, 0);
    }

    /**
     * @param $fname
     * @param $calendarId
     * @return int
     */
    public function importCSV($fname, $calendarId)
    {
        global $user;
        $smarty = TikiLib::lib('smarty');
        $fields = false;
        if (! empty($fname) && ($fhandle = fopen($fname, 'r'))) {
            $fields = fgetcsv($fhandle, 1000, escape: "");
        }
        if ($fields === false || ! array_search('name', $fields)) {
            $smarty->assign('msg', tra("The file has incorrect syntax or is not a CSV file"));
            $smarty->display("error.tpl");
            die;
        }
        $nb = 0;
        while (($data = fgetcsv($fhandle, 1000, escape: "")) !== false) {
            $d = [
                        'calendarId' => $calendarId,
                        'calitemId' => '0',
                        'name' => '',
                        'description' => '',
                        'locationId' => '',
                        'organizers' => '',
                        'participants' => '',
                        'status' => '1',
                        'priority' => '5',
                        'categoryId' => '0',
                        'newloc' => '0',
                        'newcat' => '',
                        'nlId' => '',
                        'lang' => '',
                        'start' => '',
                        'end' => ''
            ];

            foreach ($fields as $field) {
                $d[$field] = $data[array_search($field, $fields)];
            }

            if (isset($d["subject"]) && empty($d["name"])) {
                $d["name"] = $d["subject"];
            }
            if (isset($d['start date'])) {
                if (isset($d['start time'])) {
                    $d['start'] = strtotime($d['start time'], strtotime($d['start date']));
                } else {
                    $d['start'] = strtotime($d['start date']);
                }
            }
            if (isset($d['end date'])) {
                if (isset($d['end time'])) {
                    $d['end'] = strtotime($d['end time'], strtotime($d['end date']));
                } else {
                    $d['end'] = strtotime($d['end date']);
                }
            }

            if ($d['organizers']) {
                $d['organizers'] = explode(',', $d['organizers']);
            }

            if ($d['participants']) {
                $d['participants'] = array_map(function ($part) {
                    $part = explode(':', $part);
                    if (count($part) > 1) {
                        $part = [
                            'username' => $part[1],
                            'role' => $part[0]
                        ];
                    } else {
                        $part = [
                            'username' => $part[0]
                        ];
                    }
                    return $part;
                }, explode(',', $d['participants']));
            }

            // TODO do a replace if name, calendarId, start, end exists
            if (! empty($d['start']) && ! empty($d['end'])) {
                // importing is direct and doesn't go through Caldav server, for now
                $this->set_item($user, 0, $d);
                ++$nb;
            }
        }
        fclose($fhandle);
        return $nb;
    }

    /**
     * Returns an array of a maximum of $maxrows upcoming (but possibly past) events in the given $order.
     * If $calendarId is set, events not in the specified calendars are filtered. $calendarId
     * can be a calendar identifier or an array of calendar identifiers. If $maxDaysEnd is
     * a natural, events ending after $maxDaysEnd days are filtered. If $maxDaysStart is a
     * natural, events starting after $maxDaysStart days are filtered.
     * Events ending more than $priorDays in the past are filtered.
     *
     * Each event is represented by a string-indexed array with indices start, end,
     * name, description, calitemId, calendarId, user, lastModif, url, allday
     * in the same format as tiki_calendar_items fields, as well as location
     * for the event's locations, parsed for the parsed description and category
     * for the event's calendar category.
     *
     */

    //Pagination
    public function upcoming_events($maxrows = -1, $calendarId = null, $maxDaysEnd = -1, $order = 'start_asc', $priorDays = 0, $maxDaysStart = -1, $start = 0)
    {

        global $prefs;
        $cond = '';
        $bindvars = [];
        if (isset($calendarId)) {
            if (is_array($calendarId)) {
                $cond = $cond . "and (0=1";
                foreach ($calendarId as $id) {
                    $cond = $cond . " or i.`calendarId` = ? ";
                }
                $cond = $cond . ")";
                $bindvars = array_merge($bindvars, $calendarId);
            } else {
                $cond = $cond . " and i.`calendarId` = ? ";
                $bindvars[] = $calendarId;
            }
        }
        $cond .= " and `end` >= (unix_timestamp(now()) - ?*3600*24)";
        $bindvars[] = $priorDays;


        if ($maxDaysEnd > 0) {
            $maxSeconds = ($maxDaysEnd * 24 * 60 * 60);
            $cond .= " and `end` <= (unix_timestamp(now())) +" . $maxSeconds;
        }
        if ($maxDaysStart > 0) {
            $maxSeconds = ($maxDaysStart * 24 * 60 * 60);
            $cond .= " and `start` <= (unix_timestamp(now())) +" . $maxSeconds;
        }
        $ljoin = "left join `tiki_calendar_locations` as l on i.`locationId`=l.`callocId` left join `tiki_calendar_categories` as c on i.`categoryId`=c.`calcatId`";

        $query = "select i.`start`, i.`end`, i.`name`, i.`description`, i.`status`," .
                            " i.`calitemId`, i.`calendarId`, i.`user`, i.`lastModif`, i.`url`," .
                            " l.`name` as location, i.`allday`, c.`name` as category" .
                            " from `tiki_calendar_items` i $ljoin" .
                            " where 1=1 " . $cond .
                            " order by " . $this->convertSortMode($order);

        $ret = $this->fetchAll($query, $bindvars, $maxrows, $start);

        $query_cant = "select count(*) from `tiki_calendar_items` i $ljoin where 1=1 " . $cond . " GROUP BY i.calitemId order by " . $this->convertSortMode($order);
        $cant = $this->getOne($query_cant, $bindvars);

        foreach ($ret as &$res) {
            $res['parsed'] = TikiLib::lib('parser')->parse_data($res['description'], ['is_html' => $prefs['calendar_description_is_html'] === 'y', 'objectType' => 'calendar event', 'objectId' => $res['calitemId'], 'fieldName' => 'description']);
        }

        $retval = [];
        $retval['data'] = $ret;
        $retval['cant'] = $cant;
        return $retval;
    }

    /**
     * @param $maxrows
     * @param null $calendarId
     * @param $maxDaysEnd
     * @param string $order
     * @param int $priorDays
     * @param $maxDaysStart
     * @param int $start
     * @return array
     */
    public function all_events($maxrows = -1, $calendarId = null, $maxDaysEnd = -1, $order = 'start_asc', $priorDays = 0, $maxDaysStart = -1, $start = 0, $itemIds = [])
    {
        global $prefs;
        $cond = '';
        $bindvars = [];
        if (isset($calendarId)) {
            if (is_array($calendarId)) {
                $cond = $cond . "and (0=1";
                foreach ($calendarId as $id) {
                    $cond = $cond . " or i.`calendarId` = ? ";
                }
                $cond = $cond . ")";
                $bindvars = array_merge($bindvars, $calendarId);
            } else {
                $cond = $cond . " and i.`calendarId` = ? ";
                $bindvars[] = $calendarId;
            }
        }
        if (count($itemIds) > 0) {
            $cond .= " and i.calitemId in (" . implode(',', array_fill(0, count($itemIds), '?')) . ")";
            $bindvars = array_merge($bindvars, $itemIds);
        }
        $condition = '';
        $cond .= " and  $condition (unix_timestamp(now()) - ?*3600*34)";
        $bindvars[] = $priorDays;

        if ($maxDaysEnd > 0) {
            $maxSeconds = ($maxDaysEnd * 24 * 60 * 60);
            $cond .= " and `end` <= (unix_timestamp(now())) +" . $maxSeconds;
        }
        if ($maxDaysStart > 0) {
            $maxSeconds = ($maxDaysStart * 24 * 60 * 60);
            $cond .= " and `start` <= (unix_timestamp(now())) +" . $maxSeconds;
        }
        $ljoin = "left join `tiki_calendar_locations` as l on i.`locationId`=l.`callocId` left join `tiki_calendar_categories` as c on i.`categoryId`=c.`calcatId`";

        $query = "select i.`start`, i.`end`, i.`name`, i.`description`, i.`status`," .
                            " i.`calitemId`, i.`calendarId`, i.`user`, i.`lastModif`, i.`url`," .
                            " l.`name` as location, i.`allday`, c.`name` as category, i.`created`, i.`priority`, i.`uid`" .
                            " from `tiki_calendar_items` i" .
                            " $ljoin" .
                            " where 1=1 " . $cond .
                            " order by " . $this->convertSortMode($order);

        $ret = $this->fetchAll($query, $bindvars, $maxrows, $start);

        $query_cant = "select count(*) from `tiki_calendar_items` i $ljoin where 1=1 " . $cond . " order by " . $this->convertSortMode($order);
        $cant = $this->getOne($query_cant, $bindvars);

        foreach ($ret as &$res) {
            $res['parsed'] = TikiLib::lib('parser')->parse_data($res['description'], ['is_html' => $prefs['calendar_description_is_html'] === 'y', 'objectType' => 'calendar event', 'objectId' => $res['calitemId'], 'fieldName' => 'description']);
        }

        $retval = [];
        $retval['data'] = $ret;
        $retval['cant'] = $cant;
        return $retval;
    }

    public function get_events($calendarId, $itemIdsOrUris = [], $componenttype = null, $start = null, $end = null, $recurrenceId = null, $changed = null)
    {
        global $prefs;

        $cond = ' and i.calendarId = ?';
        $bindvars = [$calendarId];

        if (count($itemIdsOrUris) > 0) {
            $recurrences = array_filter($itemIdsOrUris, function ($uri) {
                return substr($uri, 0, 1) == 'r';
            });
            $itemIdsOrUris = array_diff($itemIdsOrUris, $recurrences);
            if (! $itemIdsOrUris) {
                $itemIdsOrUris[] = '';
            }
            $recurrences = array_map(function ($uri) {
                return substr($uri, 1);
            }, $recurrences);
            if (! $recurrences) {
                $recurrences[] = '';
            }
            $cond .= " and (i.calitemId in (" . implode(',', array_fill(0, count($itemIdsOrUris), '?')) . ") or i.uri in (" . implode(',', array_fill(0, count($itemIdsOrUris), '?')) . ") or i.recurrenceId in (" . implode(',', array_fill(0, count($recurrences), '?')) . ") or r.uri in (" . implode(',', array_fill(0, count($itemIdsOrUris), '?')) . "))";
            $bindvars = array_merge($bindvars, $itemIdsOrUris, $itemIdsOrUris, $recurrences, $itemIdsOrUris);
        }

        // TODO: we support only events for now. This is meant for CalDAV access to support TODO items, for example.
        // if ($componenttype) {
        //  $cond .= " and i.componenttype = ?";
        //  $bindvars[] = $componenttype;
        // }

        if ($start) {
            $cond .= " and i.end > ?";
            $bindvars[] = $start;
        }

        if ($end) {
            $cond .= " and i.start < ?";
            $bindvars[] = $end;
        }

        if ($recurrenceId) {
            $cond .= " and i.recurrenceId = ?";
            $bindvars[] = $recurrenceId;
        }

        if (! is_null($changed)) {
            $cond .= " and i.changed = ?";
            $bindvars[] = $changed;
        }

        $join = "left join `tiki_calendar_locations` as l on i.`locationId`=l.`callocId` left join `tiki_calendar_categories` as c on i.`categoryId`=c.`calcatId`";

        $query = "select i.`start`, i.`end`, i.`name`, i.`description`, i.`status`," .
                            " i.`calitemId`, i.`calendarId`, i.`user`, i.`lastmodif` as `lastModif`, i.`url`, i.`recurrenceId`, i.`recurrenceStart`, r.`uid` as `recurrenceUid`," .
                            " l.`name` as location, c.`name` as category, i.`created`, i.`priority`, i.`uid`, i.`uri`" .
                            " from `tiki_calendar_items` i" .
                            " left join `tiki_calendar_recurrence` r on i.`recurrenceId` = r.`recurrenceId`" .
                            " $join" .
                            " where 1=1 " . $cond .
                            " order by calitemId";

        return $this->fetchAll($query, $bindvars);
    }

    public function find_by_uid($user, $uid)
    {
        $query = "select i.`calendarId`, i.`calitemId`, i.`uri`, i.`recurrenceId`, i.`user` from `tiki_calendar_items` i left join `tiki_calendars` c on i.`calendarId` = c.`calendarId` left join `tiki_calendar_recurrence` r on i.`recurrenceId` = r.`recurrenceId` where (i.`uid` = ? or r.uid = ?)";
        $bindvars = [$uid, $uid];
        if ($user) {
            $query .= " and c.user = ?";
            $bindvars[] = $user;
        }
        $result = $this->query($query, $bindvars);
        return $result->fetchRow();
    }

    public function get_participant_by_event_uid($uid)
    {
        $query = "select i.`calitemId`,i.`username`,i.`role`,i.`partstat` from `tiki_calendar_roles` i join `tiki_calendar_items` c on i.`calitemId` = c.`calitemId` where c.`uid` = ?";
        $bindvars = [$uid];
        $result = $this->fetchAll($query, $bindvars);

        return $result;
    }



    /**
     * @param $maxrows
     * @param null $calendarId
     * @param $maxDaysEnd
     * @param string $order
     * @param int $priorDays
     * @param $maxDaysStart
     * @param int $start
     * @return array
     */
    public function past_events($maxrows = -1, $calendarId = null, $maxDaysEnd = -1, $order = 'start_asc', $priorDays = 0, $maxDaysStart = -1, $start = 0)
    {
        global $prefs;
        $cond = '';
        $bindvars = [];
        if (isset($calendarId)) {
            if (is_array($calendarId)) {
                $cond = $cond . "and (0=1";
                foreach ($calendarId as $id) {
                    $cond = $cond . " or i.`calendarId` = ? ";
                }
                $cond = $cond . ")";
                $bindvars = array_merge($bindvars, $calendarId);
            } else {
                $cond = $cond . " and i.`calendarId` = ? ";
                $bindvars[] = $calendarId;
            }
        }
        $cond .= " and `end` <= (unix_timestamp(now()) - ?*3600*34)";
        $bindvars[] = $priorDays;

        if ($maxDaysEnd > 0) {
            $maxSeconds = ($maxDaysEnd * 24 * 60 * 60);
            $cond .= " and `end` <= (unix_timestamp(now())) +" . $maxSeconds;
        }
        if ($maxDaysStart > 0) {
            $maxSeconds = ($maxDaysStart * 24 * 60 * 60);
            $cond .= " and `start` <= (unix_timestamp(now())) +" . $maxSeconds;
        }

        $ljoin = "left join `tiki_calendar_locations` as l on i.`locationId`=l.`callocId` left join `tiki_calendar_categories` as c on i.`categoryId`=c.`calcatId`";
        $query = "select i.`start`, i.`end`, i.`name`, i.`description`," .
                            " i.`calitemId`, i.`calendarId`, i.`user`, i.`lastModif`," .
                            " i.`url`, l.`name` as location, i.`allday`," .
                            "c.`name` as category" .
                            " from `tiki_calendar_items` i $ljoin where 1=1 " . $cond .
                            " order by " . $this->convertSortMode($order);

        $ret = $this->fetchAll($query, $bindvars, $maxrows, $start);

        $query_cant = "select count(*) from `tiki_calendar_items` i $ljoin where 1=1 " . $cond . " order by " . $this->convertSortMode($order);
        $cant = $this->getOne($query_cant, $bindvars);

        foreach ($ret as &$res) {
            $res['parsed'] = TikiLib::lib('parser')->parse_data($res['description'], ['is_html' => $prefs['calendar_description_is_html'] === 'y', 'objectType' => 'calendar event', 'objectId' => $res['calitemId'], 'fieldName' => 'description']);
        }

        $retval = [];
        $retval['data'] = $ret;
        $retval['cant'] = $cant;
        return $retval;
    }

    /**
     * @param $calendarId
     * @param $days
     * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
     */
    public function cleanEvents($calendarId, $days)
    {
        global $tikilib;
        $mid[] = " `end` < ? ";
        $bindvars[] = $tikilib->now - $days * 24 * 60 * 60;
        if ($calendarId > 0) {
            $mid[] = " `calendarId` = ? ";
            $bindvars[] = $calendarId;
        }
        $query = "delete from `tiki_calendar_items` where " . implode(' and ', $mid);
        $result = $tikilib->query($query, $bindvars);
        return $result;
    }

    /**
     * @return int
     */
    public function firstDayofWeek()
    {
        global $prefs;
        if ($prefs['calendar_firstDayofWeek'] == 'user') {
            $firstDayofWeek = (int)tra('First day of week: Sunday (its ID is 0) - Translators, you need to localize this string!');
            if ($firstDayofWeek < 1 || $firstDayofWeek > 6) {
                $firstDayofWeek = 0;
            }
        } else {
            $firstDayofWeek = $prefs['calendar_firstDayofWeek'];
        }
        return $firstDayofWeek;
    }
    // return detail on a date
    /**
     * @param $focusDate
     * @return array
     */
    public function infoDate($focusDate)
    {
        $focus = [
            'day' => (int)TikiLib::date_format('%d', $focusDate),
            'month' => (int)TikiLib::date_format('%m', $focusDate),
            'year' => TikiLib::date_format('%Y', $focusDate),
            'date' => $focusDate,
            'weekDay' => TikiLib::date_format('%w', $focusDate) // in (0, 6)
        ];
        $focus['daysInMonth'] = Date_Calc::daysInMonth($focus['month'], $focus['year']);
        return $focus;
    }
    // Compute the start date (the 1 first of the month of the focus date or the day) and the next start date from the period around a focus date
    /**
     * @param $focus
     * @param string $view
     * @param string $beginMonth
     * @param $start
     * @param $startNext
     */
    public function focusStartEnd($focus, $view, $beginMonth, &$start, &$startNext)
    {
        $nbMonths = ['month' => 1, 'bimester' => 2, 'trimester' => 3, 'quarter' => 4, 'semester' => 6, 'year' => 12];
        // start of the period
        $start = $focus;
        if ($beginMonth == 'y') {
            $start['day'] = 1;
        }
        $start['date'] = TikiLib::make_time(0, 0, 0, $start['month'], $start['day'], $start['year']);
        $start['weekDay'] = TikiLib::date_format('%w', $start['date']); // in (0, 6)
        // start of the next period - just shift some months
        $startNext['date'] = TikiLib::make_time(0, 0, 0, $start['month'] + $nbMonths[$view], $start['day'], $start['year']);
        $startNext['day'] = TikiLib::date_format('%d', $startNext['date']);
        $startNext['month'] = TikiLib::date_format('%m', $startNext['date']);
        $startNext['year'] = TikiLib::date_format('%Y', $startNext['date']);
        $startNext['weekDay'] = TikiLib::date_format('%w', $startNext['date']);
    }
    // Compute the date just $view from the focus
    /**
     * @param $focus
     * @param string $view
     * @return array
     */
    public function focusPrevious($focus, $view = 'month')
    {
        $nbMonths = ['day' => 0, 'week' => 0, 'month' => 1, 'bimester' => 2, 'trimester' => 3, 'quarter' => 4, 'semester' => 6, 'year' => 12];
        $nbDays = ['day' => 1, 'week' => 7, 'month' => 0, 'bimester' => 0, 'trimester' => 0, 'quarter' => 0, 'semester' => 0, 'year' => 0];
        $previous = $focus;
        $previous['day'] -= $nbDays[$view];
        // $tikilib->make_time() used with timezones doesn't support month = 0
        if ($previous['month'] - $nbMonths[$view] <= 0) { // need to change year
            $previous['month'] = ($previous['month'] + 11 - $nbMonths[$view]) % 12 + 1;
            $previous['year'] -= 1;
        } else {
            $previous['month'] -= $nbMonths[$view];
        }
        $previous['daysInMonth'] = Date_Calc::daysInMonth($previous['month'], $previous['year']);
        if ($previous['day'] > $previous['daysInMonth']) {
            $previous['day'] = $previous['daysInMonth'];
        }
        $previous['date'] = TikiLib::make_time(0, 0, 0, $previous['month'], $previous['day'], $previous['year']);
        $previous = $this->infoDate($previous['date']); // get back real day, month, year
        return $previous;
    }
    // Compute the date just $view after the focus
    /**
     * @param $focus
     * @param string $view
     * @return array
     */
    public function focusNext($focus, $view = 'month')
    {
        $nbMonths = ['day' => 0, 'week' => 0, 'month' => 1, 'bimester' => 2, 'trimester' => 3, 'quarter' => 4, 'semester' => 6, 'year' => 12];
        $nbDays = ['day' => 1, 'week' => 7, 'month' => 0, 'bimester' => 0, 'trimester' => 0, 'quarter' => 0, 'semester' => 0, 'year' => 0];
        $next = $focus;
        $next['day'] += $nbDays[$view];
        if ($next['month'] + $nbMonths[$view] > 12) {
            $next['month'] = ($next['month'] - 1 + $nbMonths[$view]) % 12 + 1;
            $next['year'] += 1;
        } else {
            $next['month'] += $nbMonths[$view];
        }
        $next['daysInMonth'] = Date_Calc::daysInMonth($next['month'], $next['year']);
        if ($next['day'] > $next['daysInMonth']) {
            $next['day'] = $next['daysInMonth'];
        }
        $next['date'] = TikiLib::make_time(0, 0, 0, $next['month'], $next['day'], $next['year']);
        $next = $this->infoDate($next['date']); // get back real day, month, year
        return $next;
    }
    // Compute a table view of dates (one line per week)
    // $firstWeekDay = 0 (Sunday), 1 (Monday)
    /**
     * @param $start
     * @param $startNext
     * @param string $view
     * @param int $firstWeekDay
     * @return array
     */
    public function getTableViewCells($start, $startNext, $view = 'month', $firstWeekDay = 0)
    {
        // start of the view
        $viewStart = $start;
        $nbBackDays = $start['weekDay'] < $firstWeekDay ? 6 : $start['weekDay'] - $firstWeekDay;
        if ($nbBackDays == 0) {
            $viewStart['daysInMonth'] = Date_Calc::daysInMonth($viewStart['month'], $viewStart['year']);
        } elseif ($start['day'] - $nbBackDays < 0) {
            $viewStart['month'] = $start['month'] == 1 ? 12 : $start['month'] - 1;
            $viewStart['year'] = $start['month'] == 1 ? $start['year'] - 1 : $start['year'];
            $viewStart['daysInMonth'] = Date_Calc::daysInMonth($viewStart['month'], $viewStart['year']);
            $viewStart['day'] = $viewStart['daysInMonth'] - $nbBackDays + 1;
            $viewStart['date'] = TikiLib::make_time(0, 0, 0, $viewStart['month'], $viewStart['day'], $viewStart['year']);
        } else {
            $viewStart['daysInMonth'] = Date_Calc::daysInMonth($viewStart['month'], $viewStart['year']);
            $viewStart['day'] = $viewStart['day'] - $nbBackDays;
            $viewStart['date'] = TikiLib::make_time(0, 0, 0, $viewStart['month'], $viewStart['day'], $viewStart['year']);
        }
        // echo '<br/>VIEWSTART'; print_r($viewStart);
        // end of the period
        $cell = [];

        for ($ilign = 0, $icol = 0, $loop = $viewStart, $weekDay = $viewStart['weekDay'];;) {
            if ($loop['date'] >= $startNext['date'] && $icol == 0) {
                break;
            }
            $cell[$ilign][$icol] = $loop;
            $cell[$ilign][$icol]['focus'] = $loop['date'] < $start['date'] || $loop['date'] >= $startNext['date'] ? false : true;
            $cell[$ilign][$icol]['weekDay'] = $weekDay;
            $weekDay = ($weekDay + 1) % 7;
            if ($icol >= 6) {
                ++$ilign;
                $icol = 0;
            } else {
                ++$icol;
            }
            if ($loop['day'] >= $loop['daysInMonth']) {
                $loop['day'] = 1;
                if ($loop['month'] == 12) {
                    $loop['month'] = 1;
                    $loop['year'] += 1;
                } else {
                    $loop['month'] += 1;
                }
                $loop['daysInMonth'] = Date_Calc::daysInMonth($loop['month'], $loop['year']);
            } else {
                $loop['day'] = $loop['day'] + 1;
            }
            $loop['date'] = TikiLib::make_time(0, 0, 0, $loop['month'], $loop['day'], $loop['year']);
        }
        //echo '<pre>CELL'; print_r($cell); echo '</pre>';
        return $cell;
    }

    /**
     * @param int $firstDayofWeek
     * @param $daysnames
     * @param $daysnames_abr
     */
    public function getDayNames($firstDayofWeek, &$daysnames, &$daysnames_abr)
    {
        $daysnames = [];
        $daysnames_abr = [];
        if ($firstDayofWeek == 0) {
            $daysnames[] = tra('Sunday');
            $daysnames_abr[] = tra('SU');
        }
        array_push(
            $daysnames,
            tra('Monday'),
            tra('Tuesday'),
            tra('Wednesday'),
            tra('Thursday'),
            tra('Friday'),
            tra('Saturday')
        );
        array_push(
            $daysnames_abr,
            tra('MO'),
            tra('TU'),
            tra('WE'),
            tra('TH'),
            tra('FR'),
            tra('SA')
        );
        if ($firstDayofWeek != 0) {
            $daysnames[] = tra('Sunday');
            $daysnames_abr[] = tra('SU');
        }
    }

    /**
     * Get calendar and its events
     *
     * @param $calIds
     * @param $viewstart
     * @param $viewend
     * @param $group_by
     * @param $item_name
     * @param bool $listmode if set to true populate listevents key of the returned array
     * @return array
     */
    public function getCalendar($calIds, &$viewstart, &$viewend, $group_by = '', $item_name = 'events', $listmode = false)
    {
        global $user, $prefs;

        // Global vars used by tiki-calendar_setup.php (this has to be changed)
        global $calendarViewMode, $request_day, $request_month;
        global $request_year, $dayend, $myurl;
        global $weekdays, $daysnames, $daysnames_abr;
        include('tiki-calendar_setup.php');

        $smarty = TikiLib::lib('smarty');
        $tikilib = TikiLib::lib('tiki');

        //FIXME : maxrecords = 50
        $listtikievents = $this->list_items_by_day($calIds, $user, $viewstart, $viewend, 0, 50);

        $mloop = TikiLib::date_format('%m', $viewstart);
        $dloop = TikiLib::date_format('%d', $viewstart);
        $yloop = TikiLib::date_format('%Y', $viewstart);
        $curtikidate = new TikiDate();
        $display_tz = $tikilib->get_display_timezone();
        if ($display_tz == '') {
            $display_tz = 'UTC';
        }
        $curtikidate->setTZbyID($display_tz);
        $curtikidate->setLocalTime($dloop, $mloop, $yloop, 0, 0, 0, 0);
        $listevents = [];

        // note that number of weeks starts at ZERO (i.e., zero = 1 week to display).
        for ($i = 0; $i <= $numberofweeks; $i++) {
            $weeks[] = $curtikidate->getWeekOfYear();

            foreach ($weekdays as $w) {
                $leday = [];
                if ($group_by == 'day') {
                    $key = 0;
                }
                if ($calendarViewMode['casedefault'] == 'day') {
                    $dday = $daystart;
                } else {
                    $dday = $curtikidate->getTime();
                    $curtikidate->addDays(1);
                }
                $cell[$i][$w]['day'] = $dday;

                if ($calendarViewMode['casedefault'] == 'day' or ( $dday >= $daystart && $dday <= $dayend )) {
                    $cell[$i][$w]['focus'] = true;
                } else {
                    $cell[$i][$w]['focus'] = false;
                }
                if (isset($listtikievents["$dday"])) {
                    $e = -1;

                    foreach ($listtikievents["$dday"] as $lte) {
                        $lte['desc_name'] = $lte['name'] ?? '';
                        if ($group_by_item != 'n') {
                            if ($group_by != 'day') {
                                $key = $lte['id'] . '|' . $lte['type'];
                            }
                            if (! isset($leday[$key])) {
                                $leday[$key] = $lte;
                                if ($group_by == 'day') {
                                    $leday[$key]['description'] = [$lte['where'] => [$lte['group_description']]];
                                    $leday[$key]['head'] = TikiLib::date_format($prefs['short_date_format'], $cell[$i][$w]['day']);
                                } else {
                                    $leday[$key]['description'] = ' - <b>' . $lte['when'] . '</b> : ' . tra($lte['action'] ?? '') . ' ' . $lte['description'];
                                    $leday[$key]['head'] = $lte['name'] ?? '' . ', <i>' . tra('in') . ' ' . $lte['where'] . '</i>';
                                }
                                $leday[$key]['desc_name'] = '';
                            } else {
                                $leday_item =& $leday[$key];
                                $leday_item['user'] .= ', ' . $lte['user'];

                                if (! isset($leday_item['action']) || ! is_integer($leday_item['action'])) {
                                    $leday_item['action'] = 1;
                                }
                                $leday_item['action']++;

                                if (! isset($leday_item['name']) || ! is_string($leday_item['name'])) {
                                    $leday_item['name'] = '';
                                }

                                if ($group_by == 'day') {
                                    $leday_item['name'] .= '<br />' . ($lte['name'] ?? '');
                                    $leday_item['desc_name'] = $leday_item['action'] . ' ' . tra($item_name) . ': ';
                                    $leday_item['description'][$lte['where']][] = $lte['group_description'];
                                } else {
                                    $leday_item['name'] = $lte['name'] ?? '' . ' (x ' . $leday_item['action'] . ')';
                                    $leday_item['desc_name'] = $leday_item['action'] . ' ' . tra($item_name);
                                    if ($lte['show_description'] == 'y' && ! empty($lte['description'])) {
                                        $leday_item['description'] .= ",\n<br /> - <b>" . $lte['when'] . '</b> : ' . tra($lte['action'] ?? '') . ' ' . $lte['description'];
                                        $leday_item['show_description'] = 'y';
                                    }
                                }
                            }
                        } else {
                            $e++;
                            $key = "{$lte['time']}$e";
                            $leday[$key] = $lte;
                            if (isset($lte['action'])) {
                                $lte['desc_name'] .= tra($lte['action']);
                            }
                        }
                    }
                    foreach ($leday as $key => $lte) {
                        if ($group_by == 'day') {
                            $desc = '';
                            foreach ($lte['description'] as $desc_where => $desc_items) {
                                $desc_items = array_unique($desc_items);
                                foreach ($desc_items as $desc_item) {
                                    if ($desc != '') {
                                        $desc .= '<br />';
                                    }
                                    $desc .= '- ' . $desc_item;
                                    if (! empty($lte['show_location']) && $lte['show_location'] == 'y' && $desc_where != '') {
                                        $desc .= ' <i>[' . $desc_where . ']</i>';
                                    }
                                }
                            }
                            $lte['description'] = $desc;
                        }

                        $smarty->assign('calendar_type', ( $myurl == 'tiki-action_calendar.php' ? 'tiki_actions' : 'calendar' ));
                        $smarty->assign_by_ref('item_url', $lte["url"]);
                        $smarty->assign_by_ref('cellhead', $lte["head"]);
                        $smarty->assign_by_ref('cellprio', $lte["prio"]);
                        $smarty->assign_by_ref('cellcalname', $lte["calname"]);
                        $smarty->assign('celllocation', "");
                        $smarty->assign('cellcategory', "");
                        $smarty->assign_by_ref('cellname', $lte["desc_name"]);
                        $smarty->assign('cellid', "");
                        $smarty->assign_by_ref('celldescription', $lte["description"]);
                        $smarty->assign('show_description', $lte["show_description"]);

                        if (! isset($leday[$key]["over"])) {
                            $leday[$key]["over"] = '';
                        } else {
                            $leday[$key]["over"] .= "<br />\n";
                        }
                        $leday[$key]["over"] .= $smarty->fetch("tiki-calendar_box.tpl");
                    }
                }

                if (is_array($leday)) {
                    ksort($leday);
                    $cell[$i][$w]['items'] = array_values($leday);
                }
            }
        }

        if ((isset($_SESSION['CalendarViewList']) && $_SESSION['CalendarViewList'] == 'list') || $listmode) {
            if (is_array($listtikievents)) {
                foreach ($listtikievents as $le) {
                    if (is_array($le)) {
                        foreach ($le as $e) {
                            $listevents[] = $e;
                        }
                    }
                }
            }
        }

        return [
            'cell' => $cell,
            'listevents' => $listevents,
            'weeks' => $weeks,
            'weekdays' => $weekdays,
            'daysnames' => $daysnames,
            'daysnames_abr' => $daysnames_abr,
            'trunc' => $trunc
        ];
    }

    /**
     * @param mixed $name
     * @access public
     * @return
     */
    public function getCalendarsContaining($name)
    {
        if (empty($name)) {
            return [];
        }

        $result = $this->fetchAll(
            'SELECT `name` FROM `tiki_calendars` WHERE `name` LIKE ?',
            [$name . '%'],
            10
        );

        $names = [];
        foreach ($result as $row) {
            $names[] = $row['name'];
        }

        return $names;
    }

    /**
     * @param $calitemId
     * @param null $adds
     * @param null $dels
     */
    public function update_participants($calitemId, $adds = null, $dels = null)
    {
        if (! empty($dels)) {
            foreach ($dels as $del) {
                $this->query('delete from `tiki_calendar_roles` where `calitemId`=? and `username`=? and `role`!=?', [$calitemId, $del, ROLE_ORGANIZER]);
            }
        }
        if (! empty($adds)) {
            $all = $this->fetchAll('select * from `tiki_calendar_roles` where `calitemId`=?', [$calitemId]);
            foreach ($adds as $add) {
                if (! isset($add['role']) || $add['role'] == ROLE_ORGANIZER) {
                    $add['role'] = 0;
                }
                $found = false;
                foreach ($all as $u) {
                    if ($u['username'] == $add['name'] && $u['role'] != ROLE_ORGANIZER) {
                        if ($u['role'] != $add['role']) {
                            $this->query('update `tiki_calendar_roles` set `role`=? where `calitemId`=? and `username`=?', [$add['role'], $calitemId, $add['name']]);
                        }
                        $found = true;
                        break;
                    }
                }
                if (! $found) {
                    $this->query('insert into `tiki_calendar_roles`(`calitemId`, `username`, `role`) values(?, ? ,?)', [$calitemId, $add['name'], $add['role']]);
                }
            }
        }
    }

    /**
     * Update participant status (partstat) for an attendee
     * @param $calitemId
     * @param $username
     * @param $partstat - ACCEPTED, TENTATIVE, DECLINED
     */
    public function update_partstat($calitemId, $username, $partstat)
    {
        return $this->query("update `tiki_calendar_roles` SET `partstat` = ? where calitemId = ? and username = ?", [$partstat, $calitemId, $username]);
    }

    /**
     * Adds a change record to the calendarchanges table.
     *
     * @param int $calendarId
     * @param int $calitemId
     * @param int $operation 1 = add, 2 = modify, 3 = delete.
     * @return void
     */
    public function add_change($calendarId, $calitemId, $operation)
    {
        $options = $this->get_calendar_options($calendarId);

        $this->query('insert into `tiki_calendar_changes`(`calitemId`, `synctoken`, `calendarId`, `operation`) values(?, ?, ?, ?)', [
            $calitemId,
            $options['synctoken'] ?? 1,
            $calendarId,
            $operation,
        ]);

        $this->query('replace into tiki_calendar_options(calendarId, optionName, value) values(?, ?, ?)', [
            $calendarId,
            'synctoken',
            isset($options['synctoken']) ? $options['synctoken'] + 1 : 1,
        ]);
    }

    /**
     * Gets latest change for each item in the calendar.
     *
     * @param int $calendarId
     * @param int $synctoken
     * @param int $maxrecords
     * @return array
     */
    public function get_changes($calendarId, $synctoken, $maxRecords = -1)
    {
        $query = 'select c1.calitemId, c1.operation, ci.uri
            from `tiki_calendar_changes` c1
            left join `tiki_calendar_changes` c2 on c1.calitemId = c2.calitemId and c1.synctoken < c2.synctoken
            left join `tiki_calendar_items` ci on c1.calitemId = ci.calitemId
            where c1.calendarId = ? and c1.synctoken > ? and c2.calitemId is null';
        $bindvars = [$calendarId, $synctoken];
        return $this->fetchAll($query, $bindvars, $maxRecords);
    }

    public function fill_uid($calitemId, $uid)
    {
        $this->query("update `tiki_calendar_items` set `uid` = ? where `calitemId` = ?", [$uid, $calitemId]);
    }

    /**
     * Calendar instance methods - deal with invites and shares
     */
    public function get_calendar_instances($calendarId)
    {
        $query = "select user, access, share_href, share_name, share_invite_status from `tiki_calendar_instances` where calendarId = ?";
        $bindvars = [$calendarId];
        return $this->fetchAll($query, $bindvars);
    }

    public function get_calendar_instance($instanceId)
    {
        $query = "select user, access, share_href, share_name, share_invite_status from `tiki_calendar_instances` where calendarInstanceId = ?";
        $bindvars = [$instanceId];
        $result = $this->query($query, $bindvars);
        return $result->fetchRow();
    }

    public function create_calendar_instance($data)
    {
        $query = 'insert into `tiki_calendar_instances` (`' . implode('`, `', array_keys($data)) . '`) values (' . implode(",", array_fill(0, count($data), "?")) . ')';
        $bindvars = array_values($data);
        $this->query($query, $bindvars);
        return $this->lastInsertId();
    }

    public function update_calendar_instance($calendarId, $share_href, $data)
    {
        $query = 'update `tiki_calendar_instances` set ' . implode(' = ?, ', array_keys($data)) . ' = ? where calendarId = ? and share_href = ?';
        $bindvars = array_values($data) + [$calendarId, $share_href];
        return $this->query($query, $bindvars);
    }

    public function remove_calendar_instance($calendarId, $share_href = null, $instanceId = null)
    {
        if ($shared_href) {
            return $this->query('delete from `tiki_calendar_instances` where calendarId = ? and share_href = ?', [$calendarId, $share_href]);
        }
        if ($instanceId) {
            return $this->query('delete from `tiki_calendar_instances` where calendarId = ? and calendarInstanceId = ?', [$calendarId, $instanceId]);
        }
    }

    /**
     * Subscription methods
     */
    public function get_subscriptions($user, $offset = 0, $maxRecords = -1, $sort_mode = 'name_asc', $find = '')
    {
        $query = "from tiki_calendar_subscriptions where user LIKE ?";
        $bindvars = [$user];
        if ($find) {
            $query .= " and (`name` like ? or `source` like ?)";
            $bindvars[] = '%' . $find . '%';
            $bindvars[] = '%' . $find . '%';
        }
        $query .= ' order by ' . $this->convertSortMode($sort_mode, ['name', 'source', 'subscriptionId']);
        $query_count = "select count(*) " . $query;
        $count = $this->getOne($query_count, $bindvars);
        $query = "select subscriptionId, uri, user, source, name, refresh_rate, `order`, color, strip_todos, strip_alarms, strip_attachments, lastmodif, last_sync " . $query;
        return [
            'count' => $count,
            'data' => $this->fetchAll($query, $bindvars, $maxRecords, $offset)
        ];
    }

    public function get_subscription($subscriptionId)
    {
        $result = $this->query("select * from tiki_calendar_subscriptions where subscriptionId = ?", $subscriptionId);
        return $result->fetchRow();
    }

    public function create_subscription($data)
    {
        $data['lastmodif'] = time();
        $data = $this->sanitizeSubscriptionData($data);
        $query = 'insert into `tiki_calendar_subscriptions` (`' . implode('`, `', array_keys($data)) . '`) values (' . implode(",", array_fill(0, count($data), "?")) . ')';
        $bindvars = array_values($data);
        $this->query($query, $bindvars);
        return $this->lastInsertId();
    }

    public function update_subscription($subscriptionId, $data)
    {
        $data['lastmodif'] = time();
        $data = $this->sanitizeSubscriptionData($data);
        $query = 'update `tiki_calendar_subscriptions` set `' . implode('` = ?, `', array_keys($data)) . '` = ? where subscriptionId = ?';
        $bindvars = array_merge(array_values($data), [$subscriptionId]);
        return $this->query($query, $bindvars);
    }

    public function delete_subscription($subscriptionId)
    {
        return $this->query('delete from `tiki_calendar_subscriptions` where subscriptionId = ?', [$subscriptionId]);
    }

    private function sanitizeSubscriptionData($data)
    {
        if (isset($data['order'])) {
            $data['order'] = intval($data['order']);
        }
        if (isset($data['strip_todos'])) {
            $data['strip_todos'] = intval($data['strip_todos']);
        }
        if (isset($data['strip_alarms'])) {
            $data['strip_alarms'] = intval($data['strip_alarms']);
        }
        if (isset($data['strip_attachments'])) {
            $data['strip_attachments'] = intval($data['strip_attachments']);
        }
        return $data;
    }

    /**
     * Scheduling methods
     */
    public function get_scheduling_object($user, $uri)
    {
        $query = "SELECT uri, calendardata, lastmodif, etag, size FROM `tiki_calendar_scheduling_objects` WHERE user = ? AND uri = ?";
        $bindvars = [$user, $uri];
        $result = $this->query($query, $bindvars);
        return $result->fetchRow();
    }

    public function get_scheduling_objects($user)
    {
        $query = "SELECT schedulingObjectId, calendardata, uri, lastmodif, etag, size FROM `tiki_calendar_scheduling_objects` WHERE user = ?";
        $bindvars = [$user];
        return $this->fetchAll($query, $bindvars);
    }

    public function delete_scheduling_object($user, $uri)
    {
        return $this->query('delete from `tiki_calendar_scheduling_objects` where user = ? and uri = ?', [$user, $uri]);
    }

    public function create_scheduling_object($user, $uri, $data)
    {
        $query = "insert into `tiki_calendar_scheduling_objects` (user, calendardata, uri, lastmodif, etag, size) values (?, ?, ?, ?, ?, ?)";
        $bindvars = [$user, $data, $uri, time(), md5($data), strlen($data)];
        $this->query($query, $bindvars);
        return $this->lastInsertId();
    }

    /**
     * Get delay value
     *
     * @param int|array $delay
     * @return mixed
     */
    public function get_delay($delay = 0)
    {
        if (empty($delay) || is_string($delay)) {
            return 0;
        }

        if (is_numeric($delay)) {
            return $delay;
        }

        if (is_array($delay)) {
            $delayYears = 0;
            $delayMonths = 0;
            $delayDays = 0;
            $delayMilliseconds = 0;

            if (! empty($delay['years'])) {
                $delayYears = intval($delay['years']) * 31556952;
            }
            if (! empty($delay['months'])) {
                $delayMonths = intval($delay['months']) * 2629700;
            }
            if (! empty($delay['days'])) {
                $delayDays = intval($delay['days']) * 86400;
            }
            if (! empty($delay['milliseconds'])) {
                $delayMilliseconds = $delay['milliseconds'] * 0.001;
            }

            $delay = $delayYears + $delayMonths + $delayDays + $delayMilliseconds;
            return $delay;
        }
    }

    public function update_item_categories($itemId, $managed_categories, $categories, $cat_name, $cat_desc)
    {
        $categlib = TikiLib::lib('categ');

        // The following needed to ensure category field exist for item (to be readable by list_items)
        // Update 2016: Needs to be the non-sefurl in case the feature is disabled later as this is stored in tiki_objects
        // and used in tiki-browse_categories.php and other places
        $cat_href = "tiki-ajax_services.php?controller=calendar&action=view_item&calitemId=$itemId";

        $categlib->update_object_categories($categories, $itemId, 'calendaritem', $cat_desc, $cat_name, $cat_href, $managed_categories);
    }

    /**
     * Return all start/end timestamp pairs for events in the chosen calendar
     * that the user participates in. Participation status can be anything.
     * @param int $calendarId
     * @param int $start timestamp
     * @param int $end timestamp
     * @param string $user
     */
    public function busyTimesFromCalendar($calendarId, $start, $end, $_user = null)
    {
        global $user;
        if (is_null($_user)) {
            $_user = $user;
        }
        $list = [];
        $result = $this->query("select `start`, `end` from tiki_calendar_items i, tiki_calendar_roles r where i.calendarId = ? and i.end > ? and i.start < ? and i.calitemId = r.calitemId and r.username = ?", [$calendarId, $start, $end, $_user]);
        while ($row = $result->fetchRow()) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * Helper method to check if user can admin the calendar info object passed.
     * Should either have global calendar admin permission or ability to admin
     * private calendars and this calendar belongs to the user.
     * @param array $info - calendar info object
     * @return boolean
     */
    public function canAdminCalendar(array $info): bool
    {
        global $user;

        $objectperms = Perms::get('calendar', $info['calendarId']);
        if ($objectperms->admin_calendar) {
            return true;
        }

        if ($info['private'] === 'y') {
            if ($objectperms->admin_private_calendar && $info['user'] == $user) {
                return true;
            }
        }

        return false;
    }
}
