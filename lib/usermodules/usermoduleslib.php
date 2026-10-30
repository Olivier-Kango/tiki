<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/** \file
 * \brief Manage user assigned modules
 */
/**
 * \brief Class to manage user assigned modules
 *
 * Useful only if the feature "A user can assign modules has been set" ($prefs['user_assigned_modules'])
 *
 * The first time, a user displays the page to assign modules(tiki-user_assigned_modules.php),
 * the list of modules are copied from tiki_modules to tiki_user_assigned_modules
 * This list is rebuilt if the user asks for a "restore default"
 *
 */
class UserModulesLib extends TikiLib
{
    /**
     * @param $moduleId
     * @param $user
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function unassign_user_module($moduleId, $user)
    {
        $module = $this->getUserModule($moduleId, $user);
        $query = "delete from `tiki_user_assigned_modules` where `moduleId`=? and `user`=?";
        $result = $this->query($query, [$moduleId, $user]);
        if ($module) {
            $this->closeUserColumnGap($user, $module['position'], $module['ord']);
        }
        return $result;
    }

    /**
     * @param $moduleId
     * @param $user
     * @param $position
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function set_column_user_module($moduleId, $user, $position)
    {
        $module = $this->getUserModule($moduleId, $user);
        if (! $module || $module['position'] == $position) {
            return false;
        }
        $order = $this->countUserColumnModules($user, $position) + 1;
        $query = "update `tiki_user_assigned_modules` set `position`=?, `ord`=? where `moduleId`=? and `user`=?";
        $result = $this->query($query, [$position, $order, $moduleId, $user]);
        $this->closeUserColumnGap($user, $module['position'], $module['ord']);
        return $result;
    }

    /**
     * @param $moduleId
     * @param $position
     * @param $order
     * @param $user
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function assign_user_module($moduleId, $position, $order, $user)
    {
        $query = "select * from `tiki_modules` where `moduleId`=?";
        $result = $this->query($query, [$moduleId]);
        $res = $result->fetchRow();
        $this->unassign_user_module($moduleId, $user);

        $order = min(max(1, (int) $order), $this->countUserColumnModules($user, $position) + 1);
        $query = "update `tiki_user_assigned_modules` set `ord`=`ord`+1 where `user`=? and `position`=? and `ord`>=?";
        $this->query($query, [$user, $position, $order]);

        $query = 'insert into `tiki_user_assigned_modules`(`moduleId`, `user`,`name`,`position`,`ord`,`type`) values(?,?,?,?,?,?)';
        $bindvars = [$moduleId, $user,$res['name'],$position,$order,$res['type']];
        return $this->query($query, $bindvars);
    }

    public function get_user_assigned_modules($user)
    {
        $query = "select * from `tiki_user_assigned_modules` where `user`=? order by `position` asc,`ord` asc";

        $result = $this->query($query, [$user]);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        return $ret;
    }

    public function get_user_assigned_modules_pos($user, $pos)
    {
        $query = "select * from `tiki_user_assigned_modules` where `user`=? and `position`=? order by `ord` asc";

        $result = $this->query($query, [$user, $pos]);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        return $ret;
    }

    public function get_assigned_modules_user($user, $position)
    {
        $query = "select `umod`.`name`, `umod`.`position`, `umod`.`ord`, `umod`.`type`,
                  `mod`.`title`, `mod`.`cache_time`, `mod`.`rows`, `mod`.`params`,
                  `mod`.`groups`, `umod`.`user`, `mod`.`moduleId`
                  from `tiki_user_assigned_modules` `umod`, `tiki_modules` `mod`
                  where `umod`.`moduleId`=`mod`.`moduleId` and `umod`.`user`=? and `umod`.`position`=? order by `umod`.`ord` asc";

        $result = $this->query($query, [$user, $position]);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        return $ret;
    }

    public function user_has_assigned_modules($user)
    {
        $query = "select count(`moduleId`) from `tiki_user_assigned_modules` where `user`=?";

        $result = $this->getOne($query, [$user]);
        return $result;
    }

    // Creates user assigned modules copying from tiki_modules

    /**
     * @param $user
     *
     * @return bool|Tiki\TikiDb\PdoResult
     */
    public function create_user_assigned_modules($user)
    {
        $query = "delete from `tiki_user_assigned_modules` where `user`=?";

        $this->query($query, [$user]);
        global $prefs;
        $query = "select * from `tiki_modules`";
        $result = $this->query($query, []);
        $user_groups = $this->get_user_groups($user);

        while ($res = $result->fetchRow()) {
            $mod_ok = 0;
            if ($res['type'] != "h") {
                if ($res["groups"] && $prefs['modallgroups'] != 'y') {
                    $groups = unserialize($res["groups"]);

                    $ins = array_intersect($groups, $user_groups);

                    if (count($ins) > 0) {
                        $mod_ok = 1;
                    }
                } else {
                    $mod_ok = 1;
                }
            }

            if ($mod_ok) {
                $query = "delete from `tiki_user_assigned_modules` where `moduleId`=? and `user`=?";
                $this->query($query, [$res['moduleId'],$user]);

                $query = "insert into `tiki_user_assigned_modules`
                (`moduleId`, `user`,`name`,`position`,`ord`,`type`) values(?,?,?,?,?,?)";
                $bindvars = [$res['moduleId'], $user,$res['name'],$res['position'],$res['ord'],$res['type']];
                $result2 = $this->query($query, $bindvars);
            }
        }
        return $result2 ?? false;
    }
    // Return the list of modules that can be assigned by the user
    public function get_user_assignable_modules($user)
    {
        global $prefs;
        $userlib = TikiLib::lib('user');

        $query = "select * from `tiki_modules`";
        $result = $this->query($query, []);
        $ret = [];
        $user_groups = $this->get_user_groups($user);

        while ($res = $result->fetchRow()) {
            $mod_ok = 0;

            // The module must not be assigned
            $isas = $this->getOne("select count(*) from `tiki_user_assigned_modules` where `moduleId`=? and `user`=?", [$res['moduleId'],$user]);

            if (! $isas) {
                if ($res["groups"] && $prefs['modallgroups'] != 'y' && (! $userlib->user_has_permission($user, 'tiki_p_admin'))) {
                    $groups = unserialize($res["groups"]);

                    $ins = array_intersect($groups, $user_groups);

                    if (count($ins) > 0) {
                        $mod_ok = 1;
                    }
                } else {
                    $mod_ok = 1;
                }

                if ($mod_ok) {
                    $ret[] = $res;
                }
            }
        }

        return $ret;
    }

    /**
     * Swap current module and above one
     *
     * @param $moduleId
     * @param $user
     *
     * @return bool|Tiki\TikiDb\PdoResult
     */
    public function swap_up_user_module($moduleId, $user)
    {
        return $this->swap_adjacent($moduleId, $user, '<');
    }

    /**
     * Swap current module and below one
     *
     * @param $moduleId
     * @param $user
     *
     * @return bool|Tiki\TikiDb\PdoResult
     */
    public function swap_down_user_module($moduleId, $user)
    {
        return $this->swap_adjacent($moduleId, $user, '>');
    }

    /**
     * Swap (up/down) two adjacent modules
     *
     * @param $moduleId
     * @param $user
     * @param $op
     *
     * @return bool|Tiki\TikiDb\PdoResult
     */
    public function swap_adjacent($moduleId, $user, $op)
    {
        $module = $this->getUserModule($moduleId, $user);
        if (! $module) {
            return false;
        }
        $result = $this->swapWithNeighbor($moduleId, $user, $module, $op);
        if (! $result) {
            $this->renumberUserColumn($user, $module['position']);
            $result = $this->swapWithNeighbor($moduleId, $user, $this->getUserModule($moduleId, $user), $op);
        }
        return $result;
    }

    private function swapWithNeighbor($moduleId, $user, array $module, $op)
    {
        $target = $op == '<' ? $module['ord'] - 1 : $module['ord'] + 1;

        $query = "update `tiki_user_assigned_modules` set `ord`=? where `user`=? and `position`=? and `ord`=?";
        $neighbor = $this->query($query, [$module['ord'], $user, $module['position'], $target]);
        if (! $neighbor || ! $neighbor->numRows()) {
            return false;
        }
        $query = "update `tiki_user_assigned_modules` set `ord`=? where `moduleId`=? and `user`=?";
        return $this->query($query, [$target, $moduleId, $user]);
    }

    /**
     * Rewrite the orders of a column as 1, 2, 3... keeping the current display order
     *
     * @param $user
     * @param $position
     */
    private function renumberUserColumn($user, $position)
    {
        $query = "update `tiki_user_assigned_modules` `umod`
                  inner join (
                      select `moduleId`, row_number() over (order by `ord`, `moduleId`) as `row_num`
                      from `tiki_user_assigned_modules` where `user`=? and `position`=?
                  ) `ranked` on `umod`.`moduleId`=`ranked`.`moduleId`
                  set `umod`.`ord`=`ranked`.`row_num`
                  where `umod`.`user`=?";
        $this->query($query, [$user, $position, $user]);
    }

    private function getUserModule($moduleId, $user)
    {
        $query = "select `position`, `ord` from `tiki_user_assigned_modules` where `moduleId`=? and `user`=?";
        return $this->query($query, [$moduleId, $user])->fetchRow();
    }

    private function countUserColumnModules($user, $position)
    {
        return (int) $this->getOne("select count(*) from `tiki_user_assigned_modules` where `user`=? and `position`=?", [$user, $position]);
    }

    private function closeUserColumnGap($user, $position, $order)
    {
        $query = "update `tiki_user_assigned_modules` set `ord`=`ord`-1 where `user`=? and `position`=? and `ord`>?";
        $this->query($query, [$user, $position, $order]);
    }

    /**
     * Toggle module position
     *
     * @param $moduleId
     * @param $user
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function move_module($moduleId, $user)
    {
        // Get current position
        $query = "select `position` from `tiki_user_assigned_modules` where `moduleId`=? and `user`=?";
        $r = $this->query($query, [$moduleId, $user]);
        $res = $r->fetchRow();
        return $this->set_column_user_module($moduleId, $user, ($res['position'] == 'right' ? 'left' : 'right'));
    }
    /// Add a module to all the user who have assigned module and who don't have already this module
    public function add_module_users($moduleId, $name, $position, $order, $type)
    {
        // for the user who already has this module, update only the type
        $this->query('update `tiki_user_assigned_modules` set `type`=? where `moduleId`=?', [$type,$moduleId]);
        // for the user who doesn't have this module
        $query = "select distinct t1.`user` from `tiki_user_assigned_modules` as t1 left join `tiki_user_assigned_modules` as t2 on t1.`user`=t2.`user` and t2.`moduleId`=? where t2.`moduleId` is null";
        $result = $this->query($query, [$moduleId]);
        while ($res = $result->fetchRow()) {
             $user = $res["user"];
            $query = "insert into `tiki_user_assigned_modules`(`moduleId`, `user`,`name`,`position`,`ord`,`type`)
            values(?,?,?,?,?,?)";
             $this->query($query, [$moduleId, $user,$name,$position,(int) $order,$type]);
        }
    }
}
