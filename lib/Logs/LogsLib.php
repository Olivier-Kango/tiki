<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Logs;

use Feedback;
use TikiLib;

use function count;
use function sort;
use function tr;
use function tra;
use function mb_convert_encoding;

class LogsLib extends TikiLib
{
    public function add_log($type, $message, $who = '', $ip = '', $client = '', $time = '')
    {
        global $user;
        if (empty($who)) {
            if (! empty($user)) {
                $who = $user;
            } else {
                $who = 'Anonymous';
            }
        }
        if (empty($ip)) {
            $ip = $this->get_ip_address();
        }
        if (empty($client)) {
            if (empty($_SERVER['HTTP_USER_AGENT'])) {
                $client = 'NO USER AGENT';
            } else {
                $client = $_SERVER['HTTP_USER_AGENT'];
            }
        }
        if (empty($time)) {
            $time = $this->now;
        }
        $this->add_action($type, 'system', 'system', $message, $who, $ip, $client, $time);
    }

    public function list_logs($type = '', $user = '', $offset = 0, $maxRecords = -1, $sort_mode = 'lastModif_desc', $find = '', $min = 0, $max = 0)
    {
        $actions = $this->list_actions($type, 'system', $user, $offset, $maxRecords, $sort_mode, $find, $min, $max, '', true);
        return $actions;
    }

    public function old_list_logs($type = '', $user = '', $offset = 0, $maxRecords = -1, $sort_mode = 'logtime_desc', $find = '', $min = 0, $max = 0)
    {
        $bindvars = [];
        $amid = [];
        $mid = '';

        if ($find) {
            $findesc = '%' . $find . '%';
            $amid[] = "`logmessage` like ? or `loguser` like ? or 'logip' like ?";
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        }

        if ($type) {
            $amid[] = "`logtype` = ?";
            $bindvars[] = $type;
        }

        if ($user) {
            if (is_array($user)) {
                $amid[] = '`loguser` in (' . implode(',', array_fill(0, count($user), '?')) . ')';
                foreach ($user as $u) {
                    $bindvars[] = $u;
                }
            } else {
                $amid[] = "`loguser` = ?";
                $bindvars[] = $user;
            }
        }

        if ($min) {
            $amid[] = "`logtime` > ?";
            $bindvars[] = $min;
        }

        if ($max) {
            $amid[] = "`logtime` < ?";
            $bindvars[] = $max;
        }

        if (count($amid)) {
            $mid = " where " . implode(" and ", $amid) . " ";
        }

        $query = "select `logId`,`loguser`,`logtype`,`logmessage`,`logtime`,`logip`,`logclient` ";
        $query .= " from `tiki_logs` $mid order by " . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `tiki_logs` $mid";
        $ret = $this->fetchAll($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $retval = [];
        $retval["data"] = $ret;
        $retval["cant"] = $cant;
        return $retval;
    }

    /**
     * Delete logs older than a specific date
     * @param $date
     * @return \TikiDb_Pdo_Result
     */
    public function clean_logs($date)
    {
        $query = "delete from `tiki_actionlog` where `objectType`='system' and `lastModif`<=?";
        return $this->query($query, [(int)$date]);
    }

    /**
     * Delete logs keep entries with given count
     * @param $date
     * @return \TikiDb_Pdo_Result
     */
    public function cleanWithCount($count)
    {
        $query = "delete from `tiki_actionlog` where `objectType`='system' order by `lastModif` asc limit ?";
        return $this->query($query, [(int) $count]);
    }

    /**
     * Delete logs older than a specific date
     * @param $date
     * @return \TikiDb_Pdo_Result
     */
    public function logsCount()
    {
        $query = "select count(*) from `tiki_actionlog` where `objectType`='system'";
        return (int) $this->getOne($query);
    }

    public function add_action(
        $action,
        $object,
        $objectType = 'wiki page',
        $param = '',
        $who = '',
        $ip = '',
        $client = '',
        $date = '',
        $contributions = '',
        $hash = '',
        $log = ''
    ) {
        global $user, $prefs;

        if (is_array($param)) {
            $param = http_build_query($param, '', '&');
        }

        if ($objectType == 'wiki page' && $action != 'Viewed') {
            $logObject = true; // to have the tiki_my_edit, history and mod-last_modif_pages
        } else {
            $logObject = $this->action_must_be_logged($action, $objectType);
        }

        $logCateg = false;
        if (isset($prefs['feature_categories'])) {
            $logCateg = $prefs['feature_categories'] == 'y' ? $this->action_must_be_logged('*', 'category') : false;
        }

        if (! $logObject && ! $logCateg) {
            return 0;
        }

        if ($date == '') {
            $date = $this->now;
        }

        if ($who == '') {
            global $tokenlib;
            if (isset($prefs['auth_token_access']) && $prefs['auth_token_access'] == 'y' && empty($user) && ! empty($tokenlib) && $tokenlib->ok) {
                $user = '§TOKEN§';
            } else {
                $who = $user;
            }
        }

        if ($ip == '') {
            $ip = $this->get_ip_address();
        }

        if ($client == '') {
            if (! empty($_SERVER['HTTP_USER_AGENT'])) {
                $client = substr($_SERVER['HTTP_USER_AGENT'], 0, 200);
            } elseif (defined('TIKI_CONSOLE')) {
                $client = 'Tiki console.php';
            } else {
                $client = null;
            }
        } else {
            $client = substr($client, 0, 200);
        }

        if ($logCateg) {
            $categlib = TikiLib::lib('categ');
            if ($objectType == 'comment') {
                preg_match('/type=([^&]*)/', $param, $matches);
                $categs = $categlib->get_object_categories($matches[1], $object);
            } else {
                $categs = $categlib->get_object_categories($objectType, $object);
            }
        }

        if (! empty($log)) {
            $log = serialize($log);
        }

        $actions = [];
        $nottostore = [
            'auth_ldap_adminpass',
            'auth_ldap_group_adminpass',
            'shipping_fedex_password',
            'shipping_ups_password',
            'auth_phpbb_dbpasswd',
            'zend_mail_smtp_pass',
            'unified_elastic_url',
            'proxy_pass',
        ];
        if ($logObject) {
            if (function_exists('mb_strcut')) {
                $param = mb_strcut($param, 0, 200);
            } else {
                $param = substr($param, 0, 200);
            }
            if ($logCateg && count($categs) > 0) {
                foreach ($categs as $categ) {
                    $query = "insert into `tiki_actionlog` " .
                        " (`action`, `object`, `lastModif`, `user`, `ip`, `comment`, `objectType`, `categId`, `client`, `log`) " .
                        " values(?,?,?,?,?,?,?,?,?,?)";

                    $this->query($query, [$action, $object, (int)$date, $who, $ip, $param, $objectType, $categ, $client, $log]);
                    $actions[] = $this->lastInsertId();
                }
            } elseif (! in_array($object, $nottostore)) {
                // It's possible that this action is being added during upgrade to 18.x before the `log` field has been added
                // to the database. To avoid error on doc/devtools/svnup.php, do not use the field here if $log is null
                if ($log != null) {
                    $query = "insert into `tiki_actionlog`" .
                        " (`action`, `object`, `lastModif`, `user`, `ip`, `comment`, `objectType`, `client`, `log`)" .
                        " values(?,?,?,?,?,?,?,?,?)";

                    $this->query($query, [$action, $object, (int)$date, $who, $ip, $param, $objectType, $client, $log]);
                } else {
                    $query = "insert into `tiki_actionlog`" .
                        " (`action`, `object`, `lastModif`, `user`, `ip`, `comment`, `objectType`, `client`)" .
                        " values(?,?,?,?,?,?,?,?)";

                    $this->query($query, [$action, $object, (int)$date, $who, $ip, $param, $objectType, $client]);
                }
                $actions[] = $this->lastInsertId();
            }
        }

        if (! empty($contributions)) {
            foreach ($actions as $a) {
                $query = "insert into `tiki_actionlog_params` (`actionId`, `name`, `value`) values(?,?,?)";
                foreach ($contributions as $contribution) {
                    $this->query($query, [$a, 'contribution', $contribution]);
                }
            }
        }

        if (! empty($hash)) {
            $query = "insert into `tiki_actionlog_params` (`actionId`, `name`, `value`) values(?,?,?)";
            foreach ($actions as $a) {
                foreach ($hash as $h) {
                    foreach ($h as $param => $val) {
                        $this->query($query, [$a, $param, $val]);
                    }
                }
            }
        }

        return isset($actions[0]) ? $actions[0] : 0;
    }

    /**
     * Logs API actions for monitoring and debugging purposes.
     *
     * @param string $errortitle The title of the error (optional).
     * @param string $errortype The type of the error eg. 403, 404 (optional).
     * @return void
     */
    public function api_add_action($errortitle = '', $errortype = ''): void
    {
        $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (! empty($authorization) & preg_match('/Bearer\s+(.*)/i', $authorization, $matches)) {
            $token = TikiLib::lib('api_token')->validToken($matches[1]);

            if ($token && ! empty($token['user'])) {
                $user = $token['user'];
            } else {
                $user = null;
            }
        } else {
            $user = 'anonymous';
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        $queryString = $_SERVER['QUERY_STRING'] ?? tr('empty');
        $log = [
            'authorization' => $authorization,
            'requestMethod' => $_SERVER['REQUEST_METHOD'],
            'contentType' => $_SERVER['CONTENT_TYPE'] ?? '',
            'queryString' => $queryString,
            'requestUri' => $_SERVER['REQUEST_URI'],
            'scriptName' => $_SERVER['SCRIPT_NAME'],
            'clientIp' => $_SERVER['REMOTE_ADDR'],
            'userAgent' => $_SERVER['HTTP_USER_AGENT'],
            'httpStatusCode' => $_SERVER['REDIRECT_STATUS'],
            'user' => $user
        ];

        $full_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $message = strtolower($log['requestMethod']) . ': ' . $full_url . ', status: ' . $log['httpStatusCode'];

        if (! empty($payload)) {
            $log['payload'] = $payload;
        }

        if (! empty($_GET)) {
            $log['get'] = $_GET;
        }

        if (! empty($_POST)) {
            $log['post'] = $_POST;
        }

        if (! empty($errortitle) || ! empty($errortype)) {
            $detail = [
                'code' => $errortype,
                'errortitle' => $errortitle,
                'message' => $errortitle,
            ];
            $log['response'] = $detail;
        }
        $this->add_action('api', 'system', 'system', $message, '', $log['clientIp'], $log['userAgent'], '', '', '', $log);
    }

    public function action_must_be_logged($action, $objectType)
    {
        global $prefs;

        return $this->action_is_viewed($action, $objectType, true);
    }

    public function action_is_viewed($action, $objectType, $logged = false)
    {
        global $prefs;
        static $is_viewed;

        // for previous compatibility
        // the new action are added with a if ($feature..)
        if (isset($prefs['feature_actionlog'])) {
            if ($prefs['feature_actionlog'] != 'y') {
                return true;
            }
        }


        if (! isset($is_viewed)) {
            $logActions = $this->get_all_actionlog_conf();
            $is_viewed = [];
            foreach ($logActions as $conf) {
                if ($logged) {
                    $is_viewed[$conf['objectType']][$conf['action']] = $conf['status'] == 'v' || $conf['status'] == 'y';
                } else {
                    $is_viewed[$conf['objectType']][$conf['action']] = $conf['status'] == 'v';
                }
            }
        }

        if (isset($is_viewed[$objectType][$action])) {
            return $is_viewed[$objectType][$action];
        } elseif (isset($is_viewed[$objectType]['*'])) {
            return $is_viewed[$objectType]['*'];
        } else {
            return false;
        }
    }

    public function set_actionlog_conf($action, $objectType, $status)
    {
        global $actionlogConf;
        $this->delete_actionlog_conf($action, $objectType);
        $action = str_replace('*', '%', $action);
        $query = "insert into `tiki_actionlog_conf` (`action`, `objectType`, `status`) values(?, ?, ?)";
        $this->query($query, [$action, $objectType, $status]);
        unset($actionlogConf);
    }

    public function delete_actionlog_conf($action, $objectType)
    {
        if ($action === '*') {
            $action = '%';
        }
        $query = "delete from `tiki_actionlog_conf` where `action`=? and `objectType`= ?";
        $this->query($query, [$action, $objectType]);
    }

    public function get_all_actionlog_conf()
    {
        global $actionlogConf;

        if (! isset($actionlogConf)) {
            $actionlogConf = self::get_actionlog_conf();
        }

        return $actionlogConf;
    }

    public function get_actionlog_conf($type = '%', $action = '%')
    {
        $actionlogconf = [];
        $query = "select * from `tiki_actionlog_conf`" .
            " where `objectType` like ? and `action` like ?" .
            " order by `objectType` desc, `action` asc";
        $result = $this->query($query, [$type, $action]);

        while ($res = $result->fetchRow()) {
            if ($res['action'] == '%') {
                $res['action'] = '*';
            }
            $res['code'] = self::encode_actionlog_conf($res['action'], $res['objectType']);
            $actionlogconf[] = $res;
        }

        return $actionlogconf;
    }

    public function get_actionlog_types()
    {
        $actionlogtype = [];
        $query = "select distinct `objectType` from `tiki_actionlog_conf` order by `objectType`";
        $result = $this->query($query, []);
        while ($res = $result->fetchRow()) {
            $actionlogtypes[] = $res['objectType'];
        }
        return $actionlogtypes;
    }

    public function get_actionlog_actions()
    {
        $actionlogactions = [];
        $query = "select distinct `action` from `tiki_actionlog_conf` order by `action`";
        $result = $this->query($query, []);
        while ($res = $result->fetchRow()) {
            if ($res['action'] != '%') {
                $actionlogactions[] = $res['action'];
            }
        }
        return $actionlogactions;
    }

    public function encode_actionlog_conf($action, $objectType)
    {
        return str_replace(' ', '0', $action . '_' . $objectType);
    }

    public function decode_actionlog_conf($string = '')
    {
        return explode('_', str_replace('0', ' ', $string));
    }

    public function list_actions(
        $action = '',
        $objectType = '',
        $user = '',
        $offset = 0,
        $maxRecords = -1,
        $sort_mode = 'lastModif_desc',
        $find = '',
        $start = 0,
        $end = 0,
        $categId = '',
        $all = false
    ) {
        global $prefs;
        $tikilib = TikiLib::lib('tiki');
        $contributionlib = TikiLib::lib('contribution');

        $bindvars = [];
        $bindvarsU = [];
        $bindvarsJoin = [];
        $amid = [];
        $mid1 = '';
        $where1 = '';

        if ($find) {
            $findesc = '%' . $find . '%';
            $amid[] = "(`comment` like ? or a.`action` like ? or `object` like ?)";
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        }
        if ($action) {
            $amid[] = "a.`action` = ?";
            $bindvars[] = $action;
        }
        if ($objectType) {
            $amid[] = "a.`objectType` = ?";
            $bindvars[] = $objectType;
        }
        if ($user == 'Anonymous') {
            $amid[] = "`user` = ?";
            $bindvars[] = '';
        } elseif ($user == 'Registered') {
            $amid[] = "`user` != ?";
            $bindvars[] = '';
        } elseif ($user) {
            $bindvarsU[] = 'contributor';
            if (is_array($user)) {
                $joinWhere1 = 'ap.`value` in (' . implode(',', array_fill(0, count($user), '?')) . ') and ap.`name`=? and ap.`actionId`=a.`actionId`';
                $mid1 = '`user` in (' . implode(',', array_fill(0, count($user), '?')) . ')';
                foreach ($user as $u) {
                    $bindvarsJoin[] = $tikilib->get_user_id($u);
                    $bindvarsU[] = $u;
                }
            } else {
                $joinWhere1 = 'ap.`value`=? and ap.`name`=? and ap.`actionId`=a.`actionId`';
                $mid1 = '`user` = ?';
                $bindvarsJoin[] = $tikilib->get_user_id($user);
                $bindvarsU[] = $user;
            }
        }

        if ($start) {
            $amid[] = "`lastModif` > ?";
            $bindvars[] = $start;
        }

        if ($end) {
            $amid[] = "`lastModif` < ?";
            $bindvars[] = $end;
        }

        if ($categId && $categId != 0) {
            if (is_array($categId)) {
                $amid[] = "`categId`in (?)";
                $bindvars[] = implode(",", $categId);
            } else {
                $amid[] = "`categId` = ?";
                $bindvars[] = $categId;
            }
        }

        $mid = implode(" and ", $amid);
        $where1 = null;
        if (! empty($bindvarsU)) {
            $bindvars = array_merge($bindvarsJoin, $bindvars, $bindvarsU);
            $join1 = " left join `tiki_actionlog_params` ap on $joinWhere1";
            $where1 = " ($mid1 or ap.actionId IS NOT NULL)";
        }

        $query = "select a.* from `tiki_actionlog` a" .
            " join `tiki_actionlog_conf` c on a.`action` = c.`action` and a.`objectType` = c.`objectType`" . ($all ? "" : " and (c.`status` = 'v')") .
            ($join1 ?? "") .
            " where " .
            ($mid !== '' ? $mid : "") .
            (($mid !== '' && $where1 !== null) ? " and " : "") .
            (($mid === '' && $where1 === null) ? " 1 " : "") .
            ($where1 !== null ? $where1 : "");

        $query_cant = preg_replace('/a\.\*/', 'count(1)', $query);

        $query .= " order by " . $this->convertSortMode($sort_mode);
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            if (
                $prefs['feature_contribution'] == 'y'
                && ($res['action'] == 'Created' || $res['action'] == 'Updated' || $res['action'] == 'Posted'
                    || $res['action'] == 'Replied')
            ) {
                if ($res['objectType'] == 'wiki page') {
                    $res['contributions'] = $this->get_action_contributions($res['actionId']);
                } elseif ($id = $this->get_comment_action($res)) {
                    $res['contributions'] = $this->get_action_contributions($res['actionId']);
                } else {
                    $res['contributions'] = $contributionlib->get_assigned_contributions($res['object'], $res['objectType']); // todo: do a left join
                }
            }

            if ($prefs['feature_contributor_wiki'] == 'y' && $res['objectType'] == 'wiki page') {
                $res['contributors'] = $this->get_contributors($res['actionId']);
                $res['nbContributors'] = 1 + count($res['contributors']);
            }

            if ($res['objectType'] == 'comment' && empty($res['categId'])) {
                $categlib = TikiLib::lib('categ');
                preg_match('/type=([^&]*)/', $res['comment'], $matches);
                $categs = $categlib->get_object_categories($matches[1], $res['object']);
                $i = 0;

                foreach ($categs as $categId) {
                    $res['categId'] = $categId;
                    if ($i++ > 0) {
                        $ret[] = $res;
                    }
                }
            }

            $ret[] = $res;
        }

        return ['data' => $ret, 'cant' => $cant];
    }

    public function sort_by_date($action1, $action2)
    {
        return ($action1['lastModif'] - $action2['lastModif']);
    }

    public function get_login_time($logins, $startDate, $endDate, $actions)
    {
        //FIXME
        if ($endDate > $this->now) {
            $endDate = $this->now;
        }

        $logTimes = [];

        foreach ($logins as $login) {
            if (! array_key_exists($login['user'], $logTimes)) {
                if ($login['comment'] == 'timeout' || $login['comment'] == 'logged out') {
                    $logTimes[$login['user']]['last'] = $startDate;
                } else {
                    $logTimes[$login['user']]['last'] = 0;
                }
                $logTimes[$login['user']]['time'] = 0;
                $logTimes[$login['user']]['nbLogins'] = 0;
            }

            if (strstr($login['comment'], 'logged from') || $login['comment'] == 'back') {
                if (strstr($login['comment'], 'logged from')) {
                    ++$logTimes[$login['user']]['nbLogins'];
                }
                // can be already log in
                if ($logTimes[$login['user']]['last'] == 0) {
                    $logTimes[$login['user']]['last'] = $login['lastModif'];
                }
            } elseif (($login['comment'] == 'timeout' || $login['comment'] == 'logged out') && $logTimes[$login['user']]['last'] > 0) {
                $logTimes[$login['user']]['time'] += $login['lastModif'] - $logTimes[$login['user']]['last'];
                $logTimes[$login['user']]['last'] = 0;
            }
        }

        // update time for those still logged in
        foreach ($logTimes as $user => $logTime) {
            if ($logTime['last']) {
                $logTimes[$user]['time'] += $endDate - $logTime['last'];
            }
        }

        // update time for those who were always logged in
        foreach ($actions as $action) {
            if ($action['user'] && ! array_key_exists($action['user'], $logTimes)) {
                $logTimes[$action['user']]['time'] = $endDate - $startDate;
            }
        }

        foreach ($logTimes as $user => $login) {
            $nbMin = floor($login['time'] / 60);
            $nbHour = floor($nbMin / 60);
            $nbDay = floor($nbHour / 24);
            $logTimes[$user]['secs'] = $login['time'] - $nbMin * 60;
            $logTimes[$user]['mins'] = $nbMin - $nbHour * 60;
            $logTimes[$user]['hours'] = $nbHour - $nbDay * 24;
            $logTimes[$user]['days'] = $nbDay;
        }
        return $logTimes;
    }

    public function get_volume_action($action)
    {
        $bytes = [];

        if (preg_match('/bytes=([0-9\-+]+)/', $action['comment'], $matches)) {//old syntax
            if (preg_match('/\+([0-9]+)/', $matches[1], $m)) {
                $bytes['add'] = $m[1];
            }
            if (preg_match('/\-([0-9]+)/', $matches[1], $m)) {
                $bytes['del'] = $m[1];
            }
        } else {
            if (preg_match('/add=([0-9\-+]+)/', $action['comment'], $matches)) {
                $bytes['add'] = $matches[1];
            }
            if (preg_match('/del=([0-9\-+]+)/', $action['comment'], $matches)) {
                $bytes['del'] = $matches[1];
            }
        }
        return $bytes;
    }

    public function get_comment_action($action)
    {
        if (preg_match('/comments_parentId=([0-9\-+]+)/', $action['comment'], $matches)) {
            return $matches[1];
        } elseif (preg_match('/#threadId=?([0-9\-+]+)/', $action['comment'], $matches)) {
            return $matches[1];
        } elseif (preg_match('/sheetId=([0-9]+)/', $action['comment'], $matches)) {
            return $matches[1];
        } elseif (preg_match('/postId=([0-9]+)/', $action['comment'], $matches)) {
            return $matches[1];
        } else {
            return '';
        }
    }

    public function get_stat_actions_per_user($actions)
    {
        $stats = $this->get_stat_actions_per_field($actions, 'user');

        return $stats;
    }

    public function get_stat_actions_per_field($actions, $field = 'user')
    {
        $stats = [];
        $actions_name = [];

        $actionlogConf = $this->get_all_actionlog_conf();

        foreach ($actions as $action) {
            if (strpos($action['comment'], 'logged from') === 0) {
                $action['action'] = 'login';
            }
            if (strpos($action['comment'], 'logged out') === 0) {
                $action['action'] = 'login';
            }
            $name = $action['action'] . '/' . $action['objectType'];
            $sort = $action['objectType'] . '/' . $action['action'];
            if ($this->action_is_viewed($action['action'], $action['objectType']) and ! in_array($name, $actions_name)) {
                $actions_name[$sort] = $name;
            }
        }

        ksort($actions_name);

        foreach ($actions as $action) {
            $key = $action[$field];
            if (! isset($stats[$key])) {
                $stats[$key] = array_fill_keys($actions_name, 0);
                $stats[$key][$field] = $action[$field];
            }
            $name = $action['action'] . '/' . $action['objectType'];
            if (($index = array_search($name, $actions_name)) !== false) {
                if ($field == 'object') {
                    $stats[$key]['link'] = isset($action['link']) ? $action['link'] : null;
                }
                ++$stats[$key][$name];
            }
        }

        usort($stats, function ($a, $b) {
            $firstKeyA = array_key_first($a);
            $firstKeyB = array_key_first($b);
            // compare by the first key value
            return $a[$firstKeyA] <=> $b[$firstKeyB];
        });

        return $stats;
    }

    public function get_stat_contributions_per_group($actions, $selectedGroups)
    {
        $tikilib = TikiLib::lib('tiki');
        $statGroups = [];
        foreach ($actions as $action) {
            if (
                ! empty($previousAction) && $action['lastModif'] == $previousAction['lastModif'] && $action['user'] == $previousAction['user']
                && $action['object'] == $previousAction['object']
                && $action['objectType'] == $previousAction['objectType']
            ) {
                // differ only by the categories
                continue;
            }

            if (strpos($action['comment'], 'logged from') === 0) {
                $action['action'] = 'login';
            }

            if (strpos($action['comment'], 'logged out') === 0) {
                $action['action'] = 'login';
            }

            $previousAction = $action;

            if (empty($action['user'])) {
                $groups = ['Anonymous'];
            } else {
                $groups = $tikilib->get_user_groups($action['user']);
                $groups = array_diff($groups, ['Anonymous']);
            }

            foreach ($groups as $key => $group) {
                if (isset($selectedGroups) && $selectedGroups[$group] != 'y') {
                    continue;
                }

                if (empty($action['contributions'])) {
                    continue;
                }

                foreach ($action['contributions'] as $contribution) {
                    if (! isset($statGroups[$group])) {
                        $statGroups[$group][$contribution['name']]['add'] = 0;
                        $statGroups[$group][$contribution['name']]['del'] = 0;
                        $statGroups[$group][$contribution['name']]['dif'] = 0;
                    }
                    $statGroups[$group][$contribution['name']]['add'] += $action['contributorAdd'];
                    $statGroups[$group][$contribution['name']]['del'] += $action['contributorDel'];
                    $statGroups[$group][$contribution['name']]['dif'] += $action['contributorAdd'] - $action['contributorDel'];
                }
            }
        }
        ksort($statGroups);

        return $statGroups;
    }

    public function get_action_stat_categ($actions, $categNames)
    {
        $stats = [];
        $actionlogConf = $this->get_all_actionlog_conf();

        foreach ($actions as $action) {
            //if ($action['categId'] == 0) print also stat for non categ object
            //  continue;

            if (strpos($action['comment'], 'logged from') === 0) {
                $action['action'] = 'login';
            }

            if (strpos($action['comment'], 'logged out') === 0) {
                $action['action'] = 'login';
            }
            $key = $action['categId'];

            if (! array_key_exists($key, $stats)) {
                $stats[$key]['category'] = $key ? $categNames[$key] : '';
                foreach ($actionlogConf as $conf) {
                    // don't take category
                    if ($conf['status'] == 'v' && $conf['action'] != '*') {
                        $stats[$key][$conf['action'] . '/' . $conf['objectType']] = 0;
                    }
                }
            }

            ++$stats[$key][$action['action'] . '/' . $action['objectType']];
        }
        sort($stats); //sort on the first field category

        return $stats;
    }

    public function get_action_vol_categ($actions, $categNames)
    {
        $stats = [];
        $actionlogConf = $this->get_all_actionlog_conf();

        foreach ($actions as $action) {
            //if ($action['categId'] == 0) print also stat for non categ object
            //  continue;

            if (strpos($action['comment'], 'logged from') === 0) {
                $action['action'] = 'login';
            }

            if (strpos($action['comment'], 'logged out') === 0) {
                $action['action'] = 'login';
            }

            if (! ($bytes = $this->get_volume_action($action))) {
                continue;
            }

            $key = $action['categId'];
            if (! array_key_exists($key, $stats)) {
                $stats[$key]['category'] = $key ? $categNames[$key] : '';
            }

            if (! isset($stats[$key][$action['objectType']]['add'])) {
                $stats[$key][$action['objectType']]['add'] = 0;
                $stats[$key][$action['objectType']]['del'] = 0;
                $stats[$key][$action['objectType']]['dif'] = 0;
            }
            $dif = 0;

            if (isset($bytes['add'])) {
                $stats[$key][$action['objectType']]['add'] += $bytes['add'];
                $dif = $bytes['add'];
            }

            if (isset($bytes['del'])) {
                $stats[$key][$action['objectType']]['del'] += $bytes['del'];
                $dif -= $bytes['del'];
            }

            $stats[$key][$action['objectType']]['dif'] += $dif;
        }
        sort($stats); //sort on the first field category

        return $stats;
    }

    public function get_action_vol_user_categ($actions, $categNames)
    {
        $stats = [];
        $actionlogConf = $this->get_all_actionlog_conf();

        foreach ($actions as $action) {
            //if ($action['categId'] == 0) print also stat for non categ object
            //  continue;

            if (strpos($action['comment'], 'logged from') === 0) {
                $action['action'] = 'login';
            }

            if (strpos($action['comment'], 'logged out') === 0) {
                $action['action'] = 'login';
            }

            if (
                $action['user'] == ''
                || ! ($bytes = $this->get_volume_action($action))
            ) {
                continue;
            }

            $key = $action['categId'] . '/' . $action['user'];
            if (! array_key_exists($key, $stats)) {
                $stats[$key]['category'] = $action['categId'] ? $categNames[$action['categId']] : '';
                $stats[$key]['user'] = $action['user'];
            }

            if (! isset($stats[$key][$action['objectType']]['add'])) {
                $stats[$key][$action['objectType']]['add'] = 0;
                $stats[$key][$action['objectType']]['del'] = 0;
                $stats[$key][$action['objectType']]['dif'] = 0;
            }

            $dif = 0;
            if (isset($bytes['add'])) {
                $stats[$key][$action['objectType']]['add'] += $bytes['add'];
                $dif = $bytes['add'];
            }

            if (isset($bytes['del'])) {
                $stats[$key][$action['objectType']]['del'] += $bytes['del'];
                $dif -= $bytes['del'];
            }
            $stats[$key][$action['objectType']]['dif'] += $dif;
        }
        sort($stats); //sort on the first field category

        return $stats;
    }

    public function get_action_vol_type($vols)
    {
        $types = [];
        foreach ($vols as $vol) {
            foreach ($vol as $key => $value) {
                if ($key != 'category' && $key != 'user' && ! in_array($key, $types)) {
                    $types[] = $key;
                }
            }
        }

        return $types;
    }

    public function get_actions_per_user_categ($actions, $categNames)
    {
        $stats = [];
        $actionlogConf = $this->get_all_actionlog_conf();
        foreach ($actions as $action) {
            if (empty($action['categId'])) {
                continue;
            }

            if (strpos($action['comment'], 'logged from') === 0) {
                $action['action'] = 'login';
            }

            if (strpos($action['comment'], 'logged out') === 0) {
                $action['action'] = 'login';
            }

            $key = $action['categId'] . '/' . $action['user'];
            ;

            if (! array_key_exists($key, $stats)) {
                $stats[$key]['category'] = $categNames[$action['categId']];
                $stats[$key]['user'] = $action['user'];
                foreach ($actionlogConf as $conf) {
                    // don't take category
                    if ($conf['status'] == 'v' && $conf['action'] != '*') {
                        $stats[$key][$conf['action'] . '/' . $conf['objectType']] = 0;
                    }
                }
            }
            ++$stats[$key][$action['action'] . '/' . $action['objectType']];
        }
        sort($stats); // sort on the first fields categ , then user

        return $stats;
    }

    public function in_kb($vol)
    {
        for ($i = count($vol) - 1; $i >= 0; --$i) {
            foreach ($vol[$i] as $k => $v) {
                if ($k != 'category' && $k != 'user') {
                    $vol[$i][$k]['add'] = round($vol[$i][$k]['add'] / 1024);
                    $vol[$i][$k]['del'] = round($vol[$i][$k]['del'] / 1024);
                    $vol[$i][$k]['dif'] = round($vol[$i][$k]['dif'] / 1024);
                }
            }
        }
        return $vol;
    }

    public function export($actionlogs, $unit = 'b')
    {
        foreach ($actionlogs as $action) {
            if (! isset($action['object'])) {
                $action['object'] = '';
            }

            if (! isset($action['categName'])) {
                $action['categName'] = '';
                $action['categId'] = '';
            }

            if (! isset($action['add'])) {
                $action['add'] = '';
            }

            if (! isset($action['del'])) {
                $action['del'] = '';
            }

            if (! isset($action['ip'])) {
                $action['ip'] = '';
            }

            $csv .= '"' . $action['user']
                . '","' . $this->date_format("%y%m%d", $action['lastModif'])
                . '","' . $this->date_format("%H:%M", $action['lastModif'])
                . '","' . $action['action']
                . '","' . $action['objectType']
                . '","' . $action['object']
                . '","' . $action['categName']
                . '","' . $action['categId']
                . '","' . $action['ip']
                . '","' . $unit
                . '","' . $action['add']
                . '","' . $action['del']
                . '","';

            if (isset($action['contributions'])) {
                $i = 0;
                foreach ($action['contributions'] as $contribution) {
                    if ($i++) {
                        $csv .= ',';
                    }
                    $csv .= $contribution['name'];
                }
            }
            $csv .= "\"\n";
        }

        return $csv;
    }

    public function get_action_params($actionId, $name = '')
    {
        if (empty($name)) {
            $query = "select * from `tiki_actionlog_params` where `actionId`=?";
            $ret = $this->fetchAll($query, [$actionId]);
        } else {
            $query = "select `value` from `tiki_actionlog_params` where `actionId`=? and `name`=?";
            $result = $this->query($query, [$actionId, $name]);
            $ret = [];
            while ($res = $result->fetchRow()) {
                $ret[] = $res['value'];
            }
        }

        return $ret;
    }

    public function get_action_contributions($actionId)
    {
        $query
            = "select tc.* from `tiki_contributions` tc, `tiki_actionlog_params` tp where tp.`actionId`=? and tp.`name`=? and tp.`value`=tc.`contributionId`";

        return $this->fetchAll($query, [$actionId, 'contribution']);
    }

    public function rename($objectType, $oldName, $newName)
    {
        $query
            = "update `tiki_actionlog`set `comment`= concat(?, `comment`) where `object`=? and (`objectType`=? or `objectType`= ?) and `comment` not like ?";
        $this->query($query, ["old=$oldName&amp;", $oldName, $objectType, 'comment', '%old=%']);
        $query = "update `tiki_actionlog`set `object`=? where `object`=? and (`objectType`=? or `objectType`= ?)";
        $this->query($query, [$newName, $oldName, $objectType, 'comment']);
    }

    public function update_category($actionId, $categId)
    {
        $query = "update `tiki_actionlog` set `categId`=? where `actionId`=?";
        $this->query($query, [$categId, $actionId]);
    }

    public function get_info_action($actionId)
    {
        $query = "select * from `tiki_actionlog`where `actionId`= ?";
        $result = $this->query($query, [$actionId]);
        if ($res = $result->fetchRow()) {
            return $res;
        } else {
            return null;
        }
    }

    public function get_user_registration_action($user)
    {
        $tiki_actionlog = \TikiDb::get()->table('tiki_actionlog');

        return $tiki_actionlog->fetchFullRow([
            'action' => 'register',
            'objectType' => 'system',
            'user' => $user,
            'comment' => $tiki_actionlog->like('%created account%')
        ]);
    }

    public function delete_params($actionId, $name = '')
    {
        $bindvars = [$actionId];
        if (! empty($name)) {
            $mid = 'and `name`= ?';
            $bindvars[] = $name;
        }
        $query = "delete from `tiki_actionlog_params` where `actionId`=? $mid";
        $this->query($query, $bindvars);
    }

    public function insert_params($actionId, $param, $values)
    {
        $query = "insert into `tiki_actionlog_params` (`actionId`, `name`, `value`) values(?,?,?)";

        foreach ($values as $val) {
            $this->query($query, [$actionId, $param, $val]);
        }
    }

    public function get_stat_contribution($actions, $startDate, $endDate, $unit = 'w')
    {
        $contributions = [];
        $nbCols = ceil(($endDate - $startDate) / (60 * 60 * 24));
        if ($unit != 'd') {
            $nbCols = ceil($nbCols / 7);
        }
        foreach ($actions as $action) {
            if (isset($action['contributions'])) {
                if (
                    ! empty($previousAction)
                    && $action['lastModif'] == $previousAction['lastModif']
                    && $action['user'] == $previousAction['user']
                    && $action['object'] == $previousAction['object']
                    && $action['objectType'] == $previousAction['objectType']
                ) {
                    // differ only by the categories
                    continue;
                }

                $previousAction = $action;

                foreach ($action['contributions'] as $contrib) {
                    $i = floor(($action['lastModif'] - $startDate) / (60 * 60 * 24));

                    if ($unit != 'd') {
                        $i = floor($i / 7);
                    }

                    if (empty($contributions[$contrib['contributionId']])) {
                        $contributions[$contrib['contributionId']]['name'] = $contrib['name'];
                        for ($j = 0; $j < $nbCols; ++$j) {
                            $contributions[$contrib['contributionId']]['stat'][$j]['add'] = 0;
                            $contributions[$contrib['contributionId']]['stat'][$j]['del'] = 0;
                            $contributions[$contrib['contributionId']]['stat'][$j]['nbAdd'] = 0;
                            $contributions[$contrib['contributionId']]['stat'][$j]['nbDel'] = 0;
                            $contributions[$contrib['contributionId']]['stat'][$j]['nbUpdate'] = 0;
                        }
                    }

                    if (! empty($action['add'])) {
                        $contributions[$contrib['contributionId']]['stat'][$i]['add'] += $action['add'];
                        if (empty($action['del'])) {
                            ++$contributions[$contrib['contributionId']]['stat'][$i]['nbAdd'];
                        }
                    }

                    if (! empty($action['del'])) {
                        $contributions[$contrib['contributionId']]['stat'][$i]['del'] += $action['del'];
                        if (empty($action['add'])) {
                            ++$contributions[$contrib['contributionId']]['stat'][$i]['nbDel'];
                        }
                    }

                    if (! empty($action['add']) && ! empty($action['del'])) {
                        ++$contributions[$contrib['contributionId']]['stat'][$i]['nbUpdate'];
                    }
                }
            }
        }

        return (['nbCols' => $nbCols, 'data' => $contributions]);
    }

    public function get_stat_contributions_per_user($actions)
    {
        $tab = [];

        foreach ($actions as $action) {
            if (strpos($action['comment'], 'logged from') === 0) {
                $action['action'] = 'login';
            }

            if (strpos($action['comment'], 'logged out') === 0) {
                $action['action'] = 'login';
            }

            if (isset($action['contributions'])) {
                if (
                    ! empty($previousAction)
                    && $action['lastModif'] == $previousAction['lastModif']
                    && $action['object'] == $previousAction['object']
                    && $action['objectType'] == $previousAction['objectType']
                    && $action['categId'] != $previousAction['categId']
                ) {
                    // differ only by the categories
                    continue;
                }

                $previousAction = $action;

                foreach ($action['contributions'] as $contrib) {
                    if (empty($tab[$action['user']]) or empty($tab[$action['user']]['stat'][$contrib['contributionId']])) {
                        $tab[$action['user']][$contrib['contributionId']]['name'] = $contrib['name'];
                        $tab[$action['user']][$contrib['contributionId']]['stat']['add'] = 0;
                        $tab[$action['user']][$contrib['contributionId']]['stat']['del'] = 0;
                        $tab[$action['user']][$contrib['contributionId']]['stat']['nbAdd'] = 0;
                        $tab[$action['user']][$contrib['contributionId']]['stat']['nbDel'] = 0;
                        $tab[$action['user']][$contrib['contributionId']]['stat']['nbUpdate'] = 0;
                    }

                    if ($action['contributorAdd']) {
                        $tab[$action['user']][$contrib['contributionId']]['stat']['add'] += $action['contributorAdd'];
                        if (! $action['contributorDel']) {
                            ++$tab[$action['user']][$contrib['contributionId']]['stat']['nbAdd'];
                        }
                    }

                    if ($action['contributorDel']) {
                        $tab[$action['user']][$contrib['contributionId']]['stat']['del'] += $action['contributorDel'];
                        if (! $action['contributorAdd']) {
                            ++$tab[$action['user']][$contrib['contributionId']]['stat']['nbDel'];
                        }
                    }

                    if ($action['contributorAdd'] && $action['contributorDel']) {
                        ++$tab[$action['user']][$contrib['contributionId']]['stat']['nbUpdate'];
                    }
                }
            }
        }
        ksort($tab);

        return ['data' => $tab, 'nbCols' => count($tab)];
        ;
    }

    public function get_colors($nb)
    {
        $colors[] = 'red';
        if (! --$nb) {
            return $colors;
        }
        $colors[] = 'yellow';
        if (! --$nb) {
            return $colors;
        }
        $colors[] = 'blue';
        if (! --$nb) {
            return $colors;
        }
        $colors[] = 'gray';
        if (! --$nb) {
            return $colors;
        }
        $colors[] = 'green';
        if (! --$nb) {
            return $colors;
        }
        $colors[] = 'aqua';
        if (! --$nb) {
            return $colors;
        }
        $colors[] = 'lime';
        if (! --$nb) {
            return $colors;
        }
        $colors[] = 'maroon';
        if (! --$nb) {
            return $colors;
        }
        $colors[] = 'navy';
        if (! --$nb) {
            return $colors;
        }
        $colors[] = 'black';
        if (! --$nb) {
            return $colors;
        }
        $colors[] = 'purple';
        if (! --$nb) {
            return $colors;
        }
        $colors[] = 'silver';
        if (! --$nb) {
            return $colors;
        }
        $colors[] = 'teal';
        if (! --$nb) {
            return $colors;
        }

        if ($nb > 0) {
            while (--$nb) {
                $colors[] = rand(1, 999999);
            }
        }

        return $colors;
    }

    public function draw_contribution_vol($contributionStat, $type, $contributions)
    {
        $ret = [];
        $ret['totalVol'] = 0;
        $ret['x'][] = tra('Contributions');
        $ret['color'] = $this->get_colors($contributions['cant']);
        $iy = 0;

        foreach ($contributions['data'] as $contribution) {
            $ret['label'][] = mb_convert_encoding($contribution['name'], 'ISO-8859-1', 'UTF-8');
            $vol = 0;
            for ($ix = 0; $ix < $contributionStat['nbCols']; ++$ix) {
                if (! empty($contributionStat['data'][$contribution['contributionId']]['stat'][$ix])) {
                    $vol += $contributionStat['data'][$contribution['contributionId']]['stat'][$ix][$type];
                }
            }

            $ret["y$iy"][] = $vol;
            $ret['totalVol'] += $vol;
            ++$iy;
        }

        return $ret;
    }

    public function draw_week_contribution_vol($contributionStat, $type, $contributions)
    {
        $ret = [];
        $ret['totalVol'] = 0;

        for ($i = 1, $nb = $contributionStat['nbCols']; $nb; --$nb) {
            $ret['x'][] = $i++;
        }

        $ret['color'] = $this->get_colors($contributions['cant']);
        $iy = 0;

        foreach ($contributions['data'] as $contribution) {
            $ret['label'][] = mb_convert_encoding($contribution['name'], 'ISO-8859-1', 'UTF-8');
            for ($ix = 0; $ix < $contributionStat['nbCols']; ++$ix) {
                if (
                    empty($contributionStat['data'][$contribution['contributionId']])
                    || empty($contributionStat['data'][$contribution['contributionId']]['stat'][$ix])
                ) {
                    $ret["y$iy"][] = 0;
                } else {
                    $ret["y$iy"][] = $contributionStat['data'][$contribution['contributionId']]['stat'][$ix][$type];
                    $ret['totalVol'] += $contributionStat['data'][$contribution['contributionId']]['stat'][$ix][$type];
                }
            }
            ++$iy;
        }

        return $ret;
    }

    public function draw_contribution_user($userStat, $type, $contributions)
    {
        $ret = [];
        $ret['totalVol'] = 0;

        foreach ($userStat['data'] as $user => $stats) {
            $ret['x'][] = mb_convert_encoding($user, 'ISO-8859-1', 'UTF-8');
        }

        $ret['color'] = $this->get_colors($contributions['cant']);
        $iy = 0;

        foreach ($contributions['data'] as $contribution) {
            $ret['label'][] = mb_convert_encoding($contribution['name'], 'ISO-8859-1', 'UTF-8');
            foreach ($userStat['data'] as $user => $stats) {
                if (empty($stats[$contribution['contributionId']])) {
                    $ret["y$iy"][] = 0;
                } else {
                    $ret["y$iy"][] = $stats[$contribution['contributionId']]['stat']["$type"];
                    $ret['totalVol'] += $stats[$contribution['contributionId']]['stat']["$type"];
                }
            }
            ++$iy;
        }

        return $ret;
    }

    public function draw_contribution_group($groupContributions, $type, $contributions)
    {
        $ret = [];
        $ret['totalVol'] = 0;

        foreach ($groupContributions as $group => $stats) {
            $ret['x'][] = mb_convert_encoding($group, 'ISO-8859-1', 'UTF-8');
        }

        $ret['color'] = $this->get_colors($contributions['cant']);
        $iy = 0;

        foreach ($contributions['data'] as $contribution) {
            $ret['label'][] = mb_convert_encoding($contribution['name'], 'ISO-8859-1', 'UTF-8');
            foreach ($groupContributions as $group => $stats) {
                if (empty($stats[$contribution['name']])) {
                    $ret["y$iy"][] = 0;
                } else {
                    $ret["y$iy"][] = $stats[$contribution['name']][$type];
                    $ret['totalVol'] += $stats[$contribution['name']][$type];
                }
            }
            ++$iy;
        }

        return $ret;
    }

    public function get_contributors($actionId)
    {
        $query
            = 'select uu.`login` from `tiki_actionlog_params` tap, `users_users` uu where tap.`actionId`=? and tap.`name`=? and uu.`userId`=tap.`value`';
        return $this->fetchAll($query, [$actionId, 'contributor']);
    }

    /*
     * get the contributors of the last update of a wiki page
     *
     */
    public function get_wiki_contributors($page_info)
    {
        $query = 'select distinct(uu.`login`), uu.`userId` ' .
            ' from `tiki_actionlog_params` tap, `users_users` uu , `tiki_actionlog` ta' .
            ' where tap.`actionId`= ta.`actionId` ' .
            ' and tap.`name`=? ' .
            ' and uu.`userId`=tap.`value` ' .
            ' and ta.`object`=? ' .
            ' and ta.`objectType`=? ' .
            ' and ta.`lastModif`=? ' .
            ' order by `login` asc';

        return $this->fetchAll($query, ['contributor', $page_info['pageName'], 'wiki page', $page_info['lastModif']]);
    }

    public function split_actions_per_contributors($actions, $users)
    {
        $contributorActions = [];

        foreach ($actions as $action) {
            $bytes = $this->get_volume_action($action);

            if (strpos($action['comment'], 'logged from') === 0) {
                $action['action'] = 'login';
            }

            if (strpos($action['comment'], 'logged out') === 0) {
                $action['action'] = 'login';
            }

            $nbC = isset($action['nbContributors']) ? $action['nbContributors'] : 1;

            if (isset($bytes['add'])) {
                $action['add'] = $bytes['add'];
                $action['contributorAdd'] = round($bytes['add'] / $nbC);
                $action['comment'] = 'add=' . $action['contributorAdd'];
            }

            if (isset($bytes['del'])) {
                $action['del'] = $bytes['del'];
                $action['contributorDel'] = round($bytes['del'] / $nbC);
                if (! empty($action['comment'])) {
                    $action['comment'] .= '&del=' . $action['contributorDel'];
                } else {
                    $action['comment'] = 'del=' . $action['contributorDel'];
                }
            }

            if (empty($users) || in_array($action['user'], $users)) {
                $contributorActions[] = $action;
            }

            if (isset($action['contributors'])) {
                foreach ($action['contributors'] as $contributor) {
                    if (empty($users) || in_array($contributor['login'], $users)) {
                        $action['user'] = $contributor['login'];
                        $contributorActions[] = $action;
                    }
                }
            }
        }
        return $contributorActions;
    }

    public function list_logsql($sort_mode = 'executed_at_desc', $offset = 0, $maxRecords = -1, $find = '')
    {
        global $prefs;
        $bindvars = [];

        if (! empty($find)) {
            $findesc = '%' . $find . '%';
            $amid = '`sql_query` like ? or `query_param` like ? or `tracer` like ? ';
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        }

        $query = 'select * from `tiki_sql_query_logs`' . ($find ? " where $amid" : '') . ' order by ' . $this->convertSortMode($sort_mode);
        $ret = $this->fetchAll($query, $bindvars, $maxRecords, $offset);
        $query_cant = 'select count(*) from `tiki_sql_query_logs`' . ($find ? " where $amid" : '');
        $cant = $this->getOne($query_cant, $bindvars);
        $retval = [];
        $retval['data'] = $ret;
        $retval['cant'] = $cant;

        return $retval;
    }

    public function clean_logsql()
    {
        $query = 'delete from  `tiki_sql_query_logs`';
        $this->query($query, []);
    }

    public function graph_to_jpgraph(&$jpgraph, $series, $accumulated = false, $color = 'whitesmoke', $colorLegend = 'white')
    {
        $jpgraph->SetScale('textlin');
        $jpgraph->setMarginColor($color);
        $jpgraph->xaxis->SetTickLabels($series['x']);
        $plot = [];

        for ($i = 0; isset($series["y$i"]); ++$i) {
            $plot[$i] = new \BarPlot($series["y$i"]);
            $plot[$i]->SetFillColor($series['color'][$i]);
            $plot[$i]->SetLegend($series['label'][$i]);
        }

        if ($accumulated) {
            $gbplot = new \AccBarPlot($plot);
        } else {
            $gbplot = new \GroupBarPlot($plot);
        }

        $jpgraph->legend->SetFillColor($colorLegend);
        $jpgraph->Add($gbplot);
    }

    public function insert_image($galleryId, $graph, $ext, $title, $period)
    {
        // TODO ImageGalleryRemoval23.x replace with file gallery if still needed
        Feedback::warning(tr('Logs lib `insert_image` image gallery functionality needs to be replace with file gallery equivalent'));
    }

    public function get_more_info($actions, $categNames = [])
    {
        global $tikilib, $prefs;

        foreach ($actions as &$action) {
            if (empty($action['user'])) {
                $action['user'] = 'Anonymous';
            }

            if ($action['categId'] && $categNames) {
                $action['categName'] = $categNames[$action['categId']];
            }

            if ($bytes = $this->get_volume_action($action)) {
                if (isset($bytes['add'])) {
                    $action['add'] = $bytes['add'];
                }
                if (isset($bytes['del'])) {
                    $action['del'] = $bytes['del'];
                }
            }

            switch ($action['objectType']) {
                case 'wiki page':
                    if (preg_match("/old=(.*)/", $action['comment'], $matches)) {
                        $action['link'] = 'tiki-index.php?page=' . $action['object'] . '&amp;old=' . $matches[1];
                    } else {
                        $action['link'] = 'tiki-index.php?page=' . $action['object'];
                    }
                    break;

                case 'article':
                    $action['link'] = 'tiki-read_article.php?articleId=' . $action['object'];

                    if (! isset($articleNames)) {
                        $artlib = TikiLib::lib('art');
                        $objects = $artlib->list_articles(0, -1, 'title_asc', '', 0, 0, '');
                        $articleNames = [];
                        foreach ($objects['data'] as $object) {
                            $articleNames[$object['articleId']] = $object['title'];
                        }
                    }

                    if (! empty($articleNames[$action['object']])) {
                        $action['object'] = $articleNames[$action['object']];
                    }
                    break;

                case 'category':
                    $action['link'] = 'tiki-browse_categories.php?parentId=' . $action['object'];
                    if ($categNames && ! empty($categNames[$action['object']])) {
                        $action['object'] = $categNames[$action['object']];
                    }
                    break;

                case 'forum':
                    if ($action['action'] == 'Removed') {
                        $action['link'] = 'tiki-view_forum.php?forumId=' . $action['object'] . '&'
                            . $action['comment'];// threadId dded for debug info
                    } else {
                        $action['link'] = 'tiki-view_forum_thread.php?' . $action['comment'];
                    }

                    if (! isset($forumNames)) {
                        $objects = TikiLib::lib('comments')->list_forums(0, -1, 'name_asc', '');
                        $forumNames = [];
                        foreach ($objects['data'] as $object) {
                            $forumNames[$object['forumId']] = $object['name'];
                        }
                    }

                    if (! empty($forumNames[$action['object']])) {
                        $action['object'] = $forumNames[$action['object']];
                    }
                    break;

                case 'file gallery':
                    if ($action['action'] == 'Uploaded' || $action['action'] == 'Downloaded') {
                        $action['link'] = 'tiki-upload_file.php?galleryId=' . $action['object'] . '&' . $action['comment'];
                    } else {
                        $action['link'] = 'tiki-list_file_gallery.php?galleryId=' . $action['object'];
                    }

                    if (! isset($fileGalleryNames)) {
                        $filegallib = TikiLib::lib('filegal');
                        $objects = $filegallib->list_file_galleries(0, -1, 'name_asc', 'admin', '', $prefs['fgal_root_id']);
                        foreach ($objects['data'] as $object) {
                            $fileGalleryNames[$object['id']] = $object['name'];
                        }
                    }

                    if (! empty($fileGalleryNames[$action['object']])) {
                        $action['object'] = $fileGalleryNames[$action['object']];
                    }
                    break;

                case 'comment':
                    preg_match('/type=([^&]*)(&.*)/', $action['comment'], $matches);

                    switch ($matches[1]) {
                        case 'wiki page':
                        case 'wiki+page':
                        case 'wiki%20page':
                            $action['link'] = 'tiki-index.php?page=' . $action['object'];
                            if (preg_match("/old=(.*)&amp;/", $action['comment'], $ms)) {
                                $action['link'] .= '&amp;old=' . $ms[1];
                            }
                            $action['link'] .= $matches[2];
                            break;

                        case 'file gallery':
                            $action['link'] = 'tiki-list_file_gallery.php?galleryId=' . $action['object'] . $matches[2];
                            break;
                    }

                    break;

                case 'sheet':
                    if (! isset($sheetNames)) {
                        $sheetlib = TikiLib::lib('sheet');
                        $objects = $sheetlib->list_sheets();
                        foreach ($objects['data'] as $object) {
                            $sheetNames[$object['sheetId']] = $object['title'];
                        }
                    }

                    if (! empty($sheetNames[$action['object']])) {
                        $action['object'] = $sheetNames[$action['object']];
                    }

                    $action['link'] = 'tiki-view_sheets.php?sheetId=' . $action['object'];
                    break;

                case 'blog':
                    if (! isset($blogNames)) {
                        $bloglib = TikiLib::lib('blog');
                        $objects = $bloglib->list_blogs();
                        foreach ($objects['data'] as $object) {
                            $blogNames[$object['blogId']] = $object['title'];
                        }
                    }

                    $action['link'] = 'tiki-view_blog.php?' . $action['comment'];

                    if (! empty($blogNames[$action['object']])) {
                        $action['object'] = $blogNames[$action['object']];
                    }
                    break;
            }
        }

        return $actions;
    }

    public function remove_action($actionId)
    {
        $query = 'delete from `tiki_actionlog_params` where `actionId`=?';
        $this->query($query, [$actionId]);
        $query = 'delete from `tiki_actionlog` where `actionId`=?';
        return $this->query($query, [$actionId]);
    }

    public function get_who_viewed($mystuff, $anonymous = true)
    {
        if (! $mystuff) {
            return false;
        }

        global $prefs;
        $bindvars = [];
        $mid = '';
        foreach ($mystuff as $obj) {
            // If changing type, compose rest of partial filter immediately
            if (isset($objectType) && $obj["objectType"] != $objectType) {
                $mid .= ' and `object` in (' . implode(',', array_fill(0, $thistype, '?')) . ')';
                // add comments filter if needed
                if ($comments) {
                    $bindvars = array_merge($bindvars, $comments);
                    $mid .= ' and `comment` in (' . implode(',', array_fill(0, count($comments), '?')) . ')';
                }
            }

            // If starting out, or changing type, to start new sub filter
            if (! isset($objectType) || $obj["objectType"] != $objectType) {
                $objectType = $obj["objectType"];
                if (! $mid) {
                    $mid .= ' (`objectType` = ?';
                } else {
                    $mid .= ' or `objectType` = ?';
                }

                $bindvars[] = $objectType;
                // reset comment detection and counter
                $comments = [];
                $thistype = 0;
            }

            // Just keep adding while objectType remain unchanged
            $bindvars[] = $obj["object"];
            if ($obj["comment"]) {
                // i.e. this objectType filters by comments also, not just on object (id)
                $comments[] = $obj["comment"];
            }
            $thistype++;
        }

        // compose rest of filter for last type
        $mid .= ' and `object` in (' . implode(',', array_fill(0, $thistype, '?')) . ')';
        // add comments filter if needed
        if ($comments) {
            $bindvars = array_merge($bindvars, $comments);
            $mid .= ' and `comment` in (' . implode(',', array_fill(0, count($comments), '?')) . ')';
        }
        // add date filter
        if ($prefs['user_who_viewed_my_stuff_days']) {
            $firsttime = $this->now - 3600 * 24 * $prefs['user_who_viewed_my_stuff_days'];
            $mid .= ") and `lastModif` > $firsttime";
        }

        if (! $anonymous) {
            $mid .= " and `user` != 'Anonymous'";
        }

        $mid .= " and `action` = 'Viewed'";
        $mid .= " and `user` IS NOT NULL"; // just to avoid those strange null entries
        $query = "select *, max(`lastModif`) as `lastViewed` " .
            " from `tiki_actionlog` where $mid " .
            " group by `user`, `object`, `objectType`, `comment`, `actionId`, `action`, `ip`, `categId`, `client`, `lastModif` order by `lastViewed` desc";

        $ret = $this->fetchAll($query, $bindvars);
        $ret = $this->get_more_info($ret);

        return $ret;
    }

    public function get_log_count($objectType, $action)
    {
        $query = "SELECT m.user,m.object,m.action
            FROM tiki_actionlog AS m
            INNER JOIN (
              SELECT MAX(i.lastModif) lastModif, i.user
              FROM tiki_actionlog i
              where objectType = ?
              GROUP BY i.user, i.object
            ) AS j ON (j.lastModif = m.lastModif AND j.user = m.user)";
        return $this->fetchAll($query, [$objectType]);
    }

    public function get_bigblue_login_time($logins, $startDate, $endDate, $actions)
    {
        if ($endDate > $this->now) {
            $endDate = $this->now;
        }
        $logTimes = [];
        $log = [];

        foreach ($logins as $login) {
            if ($login['objectType'] == 'bigbluebutton') {
                if ($login['action'] == 'Joined Room') {
                    if (! isset($logTimes[$login['user']][$login['object']]['starttime'])) {
                        $logTimes[$login['user']][$login['object']]['starttime'] = $login['lastModif'];
                    }
                }

                if ($login['action'] == 'Left Room') {
                    if (isset($logTimes[$login['user']][$login['object']]['starttime'])) {
                        $logTimes[$login['user']][$login['object']]['total'][] = $login['lastModif']
                            - $logTimes[$login['user']][$login['object']]['starttime'];
                        unset($logTimes[$login['user']][$login['object']]['starttime']);
                    }
                }
            }
        }

        foreach ($logTimes as $user => $object) {
            foreach ($object as $room => $times) {
                foreach ($times['total'] as $key => $time) {
                    $nbMin = floor($time / 60);
                    $nbHour = floor($nbMin / 60);
                    $nbDay = floor($nbHour / 24);
                    $log[$user][$room][$key] = floor($time / 60);
                }
            }
        }
        return $log;
    }

    public function export_bbb($actionlogs)
    {
        foreach ($actionlogs as $user => $room) {
            foreach ($room as $room_name => $values) {
                foreach ($values as $value) {
                    $csv .= '"' . $user
                        . '","' . $room_name
                        . '","' . $value
                        . '","';
                    $csv .= "\"\n";
                }
            }
        }
        return $csv;
    }

    public function delete_action($action, $object, $objectType, $comment)
    {
        $query = "delete from `tiki_actionlog` where `action` = ? and `object` = ? and `objectType` = ? and `comment` = ?";
        $this->query($query, [$action, $object, $objectType, $comment]);
    }

    /**
     * Log a revert action from another log
     *
     * @param int $actionId
     * @param string $object
     * @param string $page
     * @param array $logInfo
     * return null
     */
    public function revert_action($actionId, $object, $page, $logInfo)
    {
        if (! isset($logInfo['reverted'])) {
            $logInfoReverted = array_merge(['reverted' => true], $logInfo);
            $logInfoReverted = serialize($logInfoReverted);
            $query = "update `tiki_actionlog` set `log`= ? where `actionId`=?";
            $this->query($query, [$logInfoReverted, $actionId]);
            $type = $page . ' apply reverted';
            $message = $page . ' ' . tra('reverted');
            $this->add_action($type, $object, 'system', $message, '', '', '', '', '', '', $logInfo);
        }
    }
}
