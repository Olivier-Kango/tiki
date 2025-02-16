<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

/**
 *
 */
class BanLib extends TikiLib
{
    /**
     * @param $banId
     * @return mixed
     */
    public function get_rule($banId)
    {
        $query = 'select * from `tiki_banning` where `banId`=?';

        $result = $this->query($query, [$banId]);
        $res = $result->fetchRow();
        $query2 = 'select `section` from `tiki_banning_sections` where `banId`=?';
        $result2 = $this->query($query2, [$banId]);
        $aux = [];

        while ($res2 = $result2->fetchRow()) {
            $aux[] = $res2['section'];
        }

        $res['sections'] = $aux;
        return $res;
    }

    /**
     * @param $ip
     *
     * @return array|int
     */
    public function getValidateEmailRule($ip)
    {
        $banIp = explode('.', $ip);

        $query = "select * from `tiki_banning` where `ip1`=? and `ip2`=? and `ip3`=? and `ip4`=?";
        $result = $this->query($query, $banIp);
        $res = $result->fetchRow();

        if (! isset($res)) {
            $this->replace_rule(0, 'ip', 'validating unique email', $banIp[0], $banIp[1], $banIp[2], $banIp[3], 'user', $this->now, $this->now, 'n', '', []);
            return $this->getValidateEmailRule($ip);
        }
        $banId = $res['banId'];
        $query2 = "select `section` from `tiki_banning_sections` where `banId`=?";
        $result2 = $this->query($query2, [$banId]);
        $aux = [];

        while ($res2 = $result2->fetchRow()) {
            $aux[] = $res2['section'];
        }

        $res['sections'] = $aux;
        return $res;
    }

    /**
     * @param $banId
     * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
     */
    public function remove_rule($banId)
    {
        $query = "delete from `tiki_banning_sections` where `banId`=?";
        $this->query($query, [$banId]);
        $query = "delete from `tiki_banning` where `banId`=?";
        return $this->query($query, [$banId]);
    }

    /**
     * @param $offset
     * @param $maxRecords
     * @param $sort_mode
     * @param $find
     * @return array
     */
    public function list_rules($offset, $maxRecords, $sort_mode, $find)
    {

        if ($find) {
            $findesc = '%' . $find . '%';

            $mid = " where ((`message` like ?) or (`title` like ?))";
            $bindvars = [$findesc, $findesc];
        } else {
            $mid = "";
            $bindvars = [];
        }

        $query = "select * from `tiki_banning` $mid order by " . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `tiki_banning` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $aux = [];

            $query2 = "select * from `tiki_banning_sections` where `banId`=?";
            $result2 = $this->query($query2, [$res['banId']]);

            while ($res2 = $result2->fetchRow()) {
                $aux[] = $res2;
            }

            $res['sections'] = $aux;
            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["cant"] = $cant;
        $query = "select `banId` from `tiki_banning` where `use_dates`=? and `date_to` < FROM_UNIXTIME(?)";
        $result = $this->query($query, ['y', $this->now]);

        while ($res = $result->fetchRow()) {
            $this->remove_rule($res['banId']);
        }

        return $retval;
    }

    /**
     * @param $rules
     * @return string
     */
    public function export_rules($rules)
    {
        $csv = "banId,mode,title,ip1,ip2,ip3,ip4,user,date_from,date_to,use_dates,created,created_readable,message,sections\n";
        foreach ($rules as $rule) {
            if (! isset($rule['title'])) {
                $rule['title'] = '';
            }
            if (isset($rule['user'])) {
                $rule['ip1'] = '';
                $rule['ip2'] = '';
                $rule['ip3'] = '';
                $rule['ip4'] = '';
            }
            if ($rule['mode'] == 'ip') {
                $rule['user'] = '';
            }
            if ($rule['use_dates'] != 'y') {
                $rule['date_from'] = '';
                $rule['date_to'] = '';
            }
            if (! isset($rule['message'])) {
                $rule['message'] = '';
            }
            $csv .= '"' . $rule['banId']
                    . '","' . $rule['mode']
                    . '","' . $rule['title']
                    . '","' . $rule['ip1']
                    . '","' . $rule['ip2']
                    . '","' . $rule['ip3']
                    . '","' . $rule['ip4']
                    . '","' . $rule['user']
                    . '","' . $rule['date_from']
                    . '","' . $rule['date_to']
                    . '","' . $rule['use_dates']
                    . '","' . $rule['created']
                    . '","' . $this->date_format("%y%m%d %H:%M", $rule['created'])
                    . '","' . $rule['message'] . '","';

            if (! empty($rule['sections'])) {
                foreach ($rule['sections'] as $section) {
                    $csv .= $section['section'] . '|';
                }
                $csv = rtrim($csv, '|');
            }
            $csv .= "\"\n";
        }
        return $csv;
    }

    /**
     * @param $fname
     * @param $import_as_new
     * @return int
     * @throws Exception
     */
    public function importCSV($fname, $import_as_new)
    {
        $fields = false;
        if ($fhandle = fopen($fname, 'r')) {
            $fields = fgetcsv($fhandle, 1000, escape: "");
        }
        if ($fields === false) {
            $smarty = TikiLib::lib('smarty');

            $smarty->assign('msg', tra("The file has incorrect syntax or is not a CSV file"));
            $smarty->display("error.tpl");
            die;
        }
        $nb = 0;
        while (($data = fgetcsv($fhandle, 1000, escape: "")) !== false) {
            $d = ["banId" => "", "mode" => "", "title" => "", "ip1" => "", "ip2" => "",
                       "ip3" => "", "ip4" => "", "user" => "", "date_from" => "", "date_to" => "", "use_dates" => "", "created" => "", "created_readable" => "", "message" => ""];
            foreach ($fields as $field) {
                $d[$field] = $data[array_search($field, $fields)];
            }
            if (empty($d['message'])) {
                $d['message'] = tra('Spam is not welcome here');
            }
            if ($import_as_new) {
                $d['banId'] = 0;
            }
            $nb++;

            $this->replace_rule(
                $d['banId'],
                $d['mode'],
                $d['title'],
                $d['ip1'],
                $d['ip2'],
                $d['ip3'],
                $d['ip4'],
                $d['user'],
                strtotime($d['date_from']),
                strtotime($d['date_to']),
                $d['use_dates'],
                $d['message'],
                explode('|', $d['sections'])
            );
        }
        fclose($fhandle);
        return $nb;
    }

    /*
    banId integer(12) not null auto_increment,
      mode enum('user','ip'),
      title varchar(200),
      ip1 integer(3),
      ip2 integer(3),
      ip3 integer(3),
      ip4 integer(3),
      user varchar(200),
      date_from timestamp,
      date_to timestamp,
      use_dates char(1),
      message text,
      primary key(banId)
      */
    /**
     * @param $banId
     * @param $mode
     * @param $title
     * @param $ip1
     * @param $ip2
     * @param $ip3
     * @param $ip4
     * @param $user
     * @param $date_from
     * @param $date_to
     * @param $use_dates
     * @param $message
     * @param $sections
     * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
     */
    public function replace_rule($banId, $mode, $title, $ip1, $ip2, $ip3, $ip4, $user, $date_from, $date_to, $use_dates, $message, $sections, $attempt = 0)
    {
        if (empty($title)) {
            $title = empty($user) ? "$ip1.$ip2.$ip3.$ip4" : $user;
        }

        if (is_bool($date_from) || empty($date_from)) {
            $date_from = null;
        }

        if (is_bool($date_to) || empty($date_to)) {
            $date_to = null;
        }

        $count = TikiDb::get()->table('tiki_banning')->fetchCount(['banId' => $banId]);
        if ($banId && $count > 0) {
            $query = "update `tiki_banning` set `title`=?, `ip1`=?, `ip2`=?, `ip3`=?, `ip4`=?, `user`=?, " .
                "`date_from` = FROM_UNIXTIME(?), `date_to` = FROM_UNIXTIME(?), `use_dates` = ?, `message` = ?,`attempts` = ? where `banId`=?";

            $resultUpdate = $this->query($query, [$title, $ip1, $ip2, $ip3, $ip4, $user, $date_from, $date_to, $use_dates, $message, $attempt,$banId]);
        } else {
            $query = "insert into `tiki_banning`(`mode`,`title`,`ip1`,`ip2`,`ip3`,`ip4`,`user`,`date_from`,`date_to`,`use_dates`,`message`,`attempts`,`created`) " .
                "values(?,?,?,?,?,?,?,FROM_UNIXTIME(?),FROM_UNIXTIME(?),?,?,?,?)";
            $resultInsert = $this->query($query, [$mode, $title, $ip1, $ip2, $ip3, $ip4, $user, $date_from, $date_to, $use_dates, $message, $attempt, $this->now]);
            $banId = $this->getOne("select max(`banId`) from `tiki_banning` where `created`=?", [$this->now]);
        }

        $oldSections = TikiDb::get()->table('tiki_banning_sections')->fetchColumn('section', ['banId' => $banId]);
        $query = "delete from `tiki_banning_sections` where `banId`=?";
        $this->query($query, [$banId]);

        foreach ($sections as $section) {
            $query = "insert into `tiki_banning_sections`(`banId`,`section`) values(?,?)";

            $resultSections = $this->query($query, [$banId, $section]);
        }
        $newSections = TikiDb::get()->table('tiki_banning_sections')->fetchColumn('section', ['banId' => $banId]);

        if (isset($resultInsert)) {
            $result = $resultInsert;
        } elseif (isset($resultUpdate)) {
            // for updates, must check both tiki_banning and tiki_banning_sections to see if anything changed
            if ($resultUpdate->numRows()) {
                // something was changed in tiki_banning
                $result = $resultUpdate;
            } else {
                if ($oldSections != $newSections) {
                    // something was changed in tiki_banning_sections
                    $result = $resultSections;
                } else {
                    // update didn't change anything
                    $result = $resultUpdate;
                }
            }
        } else {
            $result = false;
        }
        return $result;
    }
}

$banlib = new BanLib();
