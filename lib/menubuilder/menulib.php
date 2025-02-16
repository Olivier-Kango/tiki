<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class MenuLib extends TikiLib
{
    public function empty_menu_cache($menuId = 0)
    {
        $cachelib = TikiLib::lib('cache');
        if ($menuId > 0) {
            $cachelib->invalidateAll('menu_' . $menuId . '_');
        } else {
            $menus = $this->list_menus();
            foreach ($menus['data'] as $menu_info) {
                $cachelib->invalidateAll('menu_' . $menu_info['menuId'] . '_');
            }
        }
    }

    public function list_menus($offset = 0, $maxRecords = -1, $sort_mode = 'menuId_asc', $find = '')
    {
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " where (`name` like ? or `description` like ?)";
            $bindvars = [$findesc,$findesc];
        } else {
            $mid = "";
            $bindvars = [];
        }

        $query = "select * from `tiki_menus` $mid order by " . $this->convertSortMode($sort_mode);
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $query_cant = "select count(*) from `tiki_menus` $mid";
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $query = "select count(*) from `tiki_menu_options` where `menuId`=?";
            $res["options"] = $this->getOne($query, [(int) $res["menuId"]]);
            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["cant"] = $cant;
        return $retval;
    }

    public function replace_menu($menuId, $name, $description = '', $type = 'd', $icon = null, $use_items_icons = 'n', $parse = 'n')
    {
        // Check the name
        if (isset($menuId) and $menuId > 0) {
            $query = "update `tiki_menus` set `name`=?,`description`=?,`type`=?, `icon`=?, `use_items_icons`=?, `parse`=? where `menuId`=?";
            $bindvars = [$name,$description,$type,$icon,$use_items_icons,$parse,(int) $menuId];
            $this->empty_menu_cache($menuId);
        } else {
            // was: replace into. probably we need a delete here
            $query = "insert into `tiki_menus` (`name`,`description`,`type`,`icon`,`use_items_icons`,`parse`) values(?,?,?,?,?,?)";
            $bindvars = [$name,$description,$type,$icon,$use_items_icons,$parse];
        }

        $result = $this->query($query, $bindvars);
        return true;
    }

    public function clone_menu($menuId, $name, $description = '')
    {
        $menus = $this->table('tiki_menus');
        $row = $menus->fetchFullRow([    'menuId' => $menuId ]);
        $row['menuId'] = null;
        $row['name'] = $name;
        $row['description'] = $description;
        $newId = $menus->insert($row);

        $menuoptions = $this->table('tiki_menu_options');
        $oldoptions = $menuoptions->fetchAll($menuoptions->all(), [ 'menuId' => $menuId ]);
        $row = null;

        foreach ($oldoptions as $row) {
            $row['optionId'] = null;
            $row['menuId'] = $newId;
            $menuoptions->insert($row);
        }
    }

    /*
     * Replace the current menu options for id 42 with what's in tiki.sql
     */
    public function reset_app_menu()
    {
        $tiki_sql = file_get_contents('db/tiki.sql');
        preg_match_all('/^(?:INSERT|UPDATE) (?:INTO )?`?tiki_menu_options`? .*$/mi', $tiki_sql, $matches);

        if ($matches && count($matches[0])) {
            $menuoptions = $this->table('tiki_menu_options');
            $menuoptions->deleteMultiple([ 'menuId' => 42 ]);

            foreach ($matches[0] as $query) {
                $this->query($query);
            }
            $this->empty_menu_cache(42);
        }
    }

    public function get_max_option($menuId)
    {
        $query = "select max(`position`) from `tiki_menu_options` where `menuId`=?";

        $max = $this->getOne($query, [(int) $menuId]);
        return $max;
    }

    public function replace_menu_option($menuId, $optionId, $name, $url, $type = 'o', $position = 1, $section = '', $perm = '', $groupname = '', $level = 0, $icon = '', $class = '')
    {
        if ($optionId) {
            $query = "update `tiki_menu_options` set `name`=?,`url`=?,`type`=?,`position`=?,`section`=?,`perm`=?,`groupname`=?,`userlevel`=?,`icon`=?,`class`=?  where `optionId`=?";
            $bindvars = [$name,$url,$type,(int) $position,$section,$perm,$groupname,$level,$icon,$class,$optionId];
        } else {
            $query = "insert ignore into `tiki_menu_options`(`menuId`,`name`,`url`,`type`,`position`,`section`,`perm`,`groupname`,`userlevel`,`icon`,`class`) values(?,?,?,?,?,?,?,?,?,?,?)";
            $bindvars = [(int) $menuId,$name,$url,$type,(int) $position,$section,$perm,$groupname,$level,$icon,$class];
        }

        $this->empty_menu_cache($menuId);
        $result = $this->query($query, $bindvars, -1, -1, TikiDb::ERR_EXCEPTION);
        if (! $optionId) {
            $optionId = $this->lastInsertId();
        }
        return $optionId;
    }

    public function remove_menu($menuId)
    {
        $query = "delete from `tiki_menus` where `menuId`=?";
        $result = $this->query($query, [(int) $menuId]);

        $options = $this->list_menu_options($menuId);
        foreach ($options["data"] as $option) {
            TikiLib::lib('attribute')->set_attribute('menu', $option['optionId'], 'tiki.menu.templatedgroupid', null);
        }

        $query = "delete from `tiki_menu_options` where `menuId`=?";
        $result = $this->query($query, [(int) $menuId]);

        $this->empty_menu_cache($menuId);
        return true;
    }

    public function remove_menu_option($optionId)
    {
        $query = "select `menuId` from `tiki_menu_options` where `optionId`=?";
        $menuId = $this->getOne($query, [(int) $optionId]);

        $query = "delete from `tiki_menu_options` where `optionId`=?";
        $result = $this->query($query, [(int) $optionId]);
        TikiLib::lib('attribute')->set_attribute('menu', $optionId, 'tiki.menu.templatedgroupid', null);

        $this->empty_menu_cache($menuId);
        return true;
    }

    public function get_menu_option($optionId)
    {
        $query = "select * from `tiki_menu_options` where `optionId`=?";

        $result = $this->query($query, [(int) $optionId]);

        if (! $result->numRows()) {
            return false;
        }

        $res = $result->fetchRow();
        return $res;
    }

    public function prev_pos($optionId)
    {
        $query = "select `position`, `menuId` from  `tiki_menu_options` where  `optionId` =?";
        $result = $this->query($query, [$optionId]);
        if (! ($res = $result->fetchRow())) {
            return;
        }
        $position1 = $res['position'];
        $menuId = $res['menuId'];
        $query = "select `position` from `tiki_menu_options` where `menuId` =? and `position` < ? order by `position` desc";
        if (! ($position = $this->getOne($query, [$menuId, $position1]))) {
            return;
        }
        $query = "update `tiki_menu_options` set `position`=? where `position`=? and `menuId`=? ";
        $result = $this->query($query, [$position1, $position, $menuId]);
        $query = "update `tiki_menu_options` set `position`=? where `optionId`=?";
        $result = $this->query($query, [$position, $optionId,]);

        $this->empty_menu_cache($menuId);
    }

    public function next_pos($optionId)
    {
        $query = "select `position`, `menuId` from  `tiki_menu_options` where  `optionId` =?";
        $result = $this->query($query, [$optionId]);
        if (! ($res = $result->fetchRow())) {
            return;
        }
        $position1 = $res['position'];
        $menuId = $res['menuId'];
        $query = "select `position` from `tiki_menu_options` where `menuId` =? and `position` > ? order by `position` asc";
        if (! $position = $this->getOne($query, [$menuId, $position1])) {
            return;
        }
        $query = "update `tiki_menu_options` set `position`=? where `position`=? and `menuId`=? ";
        $result = $this->query($query, [$position1, $position, $menuId]);
        $query = "update `tiki_menu_options` set `position`=? where `optionId`=?";
        $result = $this->query($query, [$position, $optionId]);

        $this->empty_menu_cache($menuId);
    }

    /**
     * Add parent option to each of the result of list_menu_options
     *
     * Formerly created the field "type_description" with description of the now deprecated type
     *
     * @param array $options
     * @return array
     */
    public function prepare_options_for_editing($options)
    {
        if (isset($options['data'])) {
            $cant = $options['cant'];
            $options = $options['data'];
        }

        $treeOut = [];

        $types = [
            's' => tra('section level 0'),
            "r" => tra('sorted section level 0'),
            '1' => tra('section level 1'),
            '2' => tra('section level 2'),
            '3' => tra('section level 3'),
            'o' => tra('option'),
            '-' => tra('separator'),
        ];

        $current_parents_branch = [];

        $treeOut = array_map(function ($option) use (&$current_parents_branch, &$types) {
            if ($current_parents_branch) {
                $parentId = $current_parents_branch[count($current_parents_branch) - 1];
            } else {
                $parentId = 0;
            }
            if ($option['type'] === 's' || $option['type'] === 'r') {
                array_splice($current_parents_branch, 0);
                $option['parent'] = 0;
                array_push($current_parents_branch, $option['optionId']);
            } elseif (is_numeric($option['type'])) {
                array_splice($current_parents_branch, (int) $option['type']);
                $option['parent'] = $parentId;
                array_push($current_parents_branch, $option['optionId']);
            } elseif ($option['type'] === 'o') {
                $option['parent'] = $parentId;
            } elseif ($option['type'] === '-') {
                array_pop($current_parents_branch);
            }

            $option['type_description'] = $types[$option['type']];

            return $option;
        }, $options);

        $treeOut = array_filter($treeOut, function ($option) {
            return $option['type'] !== '-';
        });

        if (isset($cant)) {
            $options = [
                'data' => $treeOut,
                'cant' => $cant
            ];
        }

        return $options;
    }

    // rename all the url of the form ((pageName))
    public function rename_wiki_page($oldName, $newName)
    {
        $query = "update `tiki_menu_options` set `url`=? where `url`=?";
        $result = $this->query($query, ['((' . $newName . '))', '((' . $oldName . '))']);
        $query = "select `menuId` from `tiki_menu_options` where `url`=?";
        $result = $this->fetchAll($query, ['((' . $newName . '))']);
        foreach ($result as $p) {
            $this->empty_menu_cache($p['menuId']);
        }

        // try to change some tiki-index.php?page - very limitted: for another http://anothersite/tiki-index.php?page= must not be changed
        $query = "select * from `tiki_menu_options` where `url` like ?";
        $result = $this->query($query, ["%tiki-index.php?page=$oldName%"]);
        $query = "update `tiki_menu_options` set `url`=? where `optionId`=?";

        $menu_cache_removed = [];
        while ($res = $result->fetchRow()) {
            $p = parse_url($res['url']);
            if ($p['path'] == 'tiki-index.php') {
                parse_str($p['query'], $p);
                if ($p['page'] == $oldName) {
                    $url = str_replace($oldName, $newName, $res['url']);
                    $this->query($query, [$url, $res['optionId']]);
                    if (! isset($menu_cache_removed[$p['menuId']])) {
                        $menu_cache_removed[$p['menuId']] = 1;
                        $this->empty_menu_cache($p['menuId']);
                    }
                }
            }
        }
    }

    // look if the current url matches the menu option - to be improved a lot
    public function menuOptionMatchesUrl($option)
    {
        global $prefs;
        if (empty($option['url'])) {
            return false;
        }
        $url = str_replace('+', ' ', str_replace('&amp;', '&', urldecode($_SERVER['REQUEST_URI'])));
        $option['url'] = str_replace('+', ' ', str_replace('&amp;', '&', urldecode($option['url'])));
        if (strstr($option['url'], 'structure=') && ! strstr($url, 'structure=')) {
            // try to find al the occurence of the page in structures
            $option['url'] = preg_replace('/&structure=.*/', '', $option['url']);
        }
        if (preg_match('/.*tiki.index.php$/', $url)) {
            $wikilib = TikiLib::lib('wiki');
            $homePage = $wikilib->get_default_wiki_page();
            $url .= "?page=$homePage";
        }
        if (preg_match('/.*tiki.index.php$/', $option['url'])) {
            $wikilib = TikiLib::lib('wiki');
            $homePage = $wikilib->get_default_wiki_page();
            $option['url'] .= "?page=$homePage";
        }
        $pos = false;
        if ($prefs['feature_sefurl'] == 'y' && ! empty($option['sefurl'])) {
            $pos = strpos($url, '/' . str_replace('&amp;', '&', urldecode($option['sefurl']))); // position in $url
            $lg = 1 + strlen($option['sefurl']);
        }
        if ($pos === false) {
            $pos = strpos(strtolower($url), strtolower($option['url']));
            $lg = strlen($option['url']);
        }
        if ($pos !== false) {
            $last = $pos + $lg;
            if ($last >= strlen($url) || $url[$last] == '#' || $url[$last] == '?' || $url[$last] == '&') {
                return true;
            }
        }
        return false;
    }

    // assign selected and selectedAscendant to a menu
    // sectionLevel ->shows only the list of submenus where the url is find in this level
    // toLevel -> do not show more than this level
    // also sets setion open/close according to javascript and cookies
    public function setSelected($channels, $sectionLevel = '', $toLevel = '', $params = '')
    {
        if (! empty($params['subMenu'])) {
            $subMenu = [];
            $cant = 0;
            $in = false;
            $optionLevel = $level = 0;
            foreach ($channels['data'] as $position => $option) {
                if (is_numeric($option['type'])) {
                    $optionLevel = $option['type'];
                } elseif ($option['type'] == '-') {
                    $optionLevel = $optionLevel - 1;
                } elseif ($option['type'] == 'r' || $option['type'] == 's') {
                    $optionLevel = 0;
                }
                if ($in && $optionLevel <= $level) {
                    break;
                } elseif ($in) {
                    $subMenu[] = $option;
                    $cant++;
                } elseif (! $in && $option['optionId'] == $params['subMenu']) {
                    $level = $optionLevel;
                    $in = true;
                }
                if ($option['type'] != '-' && $option['type'] != 'o') {
                    ++$optionLevel;
                }
            }
            $channels = ['data' => $this->lower($subMenu), 'cant' => $cant];
        }
        $selecteds = [];
        $optionLevel = 0;
        if (is_numeric($sectionLevel)) {
            // must extract only the submenu level sectionLevel where the current url is
            $findUrl = false;
            $cant = 0;
            foreach ($channels['data'] as $position => $option) {
                if (is_numeric($option['type'])) {
                    $optionLevel = $option['type'];
                } elseif ($option['type'] == '-') {
                    $optionLevel = $optionLevel - 1;
                } elseif ($option['type'] == 'r' || $option['type'] == 's') {
                    $optionLevel = 0;
                }
                if ($optionLevel < $sectionLevel) {
                    //close the submenu
                    if ($findUrl) {
                        break;
                    }
                    if (! empty($subMenu)) {
                        unset($subMenu);
                    }
                    $cant = 0;
                }
                if ($optionLevel >= $sectionLevel - 1 && ! empty($option['url']) && $this->menuOptionMatchesUrl($option)) {
                    $findUrl = true;
                }
                if ($optionLevel >= $sectionLevel) {
                    $subMenu[] = $option;
                    ++$cant;
                    if (empty($selectedPosition) && $option['type'] != 'o' && $option['type'] != '-') {
                        // not pretty but works - optionLevel will get "shifted up" by $sectionLevel later in lower()
                        $selecteds[$optionLevel - $sectionLevel] = $cant - 1;
                    }
                    if (! empty($option['url']) && $this->menuOptionMatchesUrl($option)) {
                        $findUrl = true;
                        $selectedPosition = $cant - 1;
                    }
                }
                if ($option['type'] != '-' && $option['type'] != 'o') {
                    ++$optionLevel;
                }
            }
            if (! empty($subMenu) && $findUrl && $cant) {
                $subMenu = $this->lower($subMenu);
                $channels['data'] = $subMenu;
                $channels['cant'] = $cant;
            } else {
                $channels['data'] = [];
                $channels['cant'] = 0;
            }
        } else {
            foreach ($channels['data'] as $position => $option) {
                if (is_numeric($option['type'])) {
                    $optionLevel = $option['type'];
                } elseif ($option['type'] == '-') {
                    $optionLevel = $optionLevel - 1;
                } elseif ($option['type'] == 'r' || $option['type'] == 's') {
                    $optionLevel = 0;
                }
                if ($option['type'] != 'o' && $option['type'] != '-') {
                    $selecteds[$optionLevel] = $position;
                }
                if ($this->menuOptionMatchesUrl($option)) {
                    $selectedPosition = $position;
                    break;
                }
                if ($option['type'] != '-' && $option['type'] != 'o') {
                    ++$optionLevel;
                }
            }
        }
        if (isset($selectedPosition)) {
            $channels['data'][$selectedPosition]['selected'] = true;
            for ($o = 0; $o < $optionLevel; ++$o) {
                if ($o !== $selectedPosition) {
                    $channels['data'][$selecteds[$o]]['selectedAscendant'] = true;
                }
            }
        }
        if (is_numeric($toLevel)) {
            $subMenu = [];
            $cant = 0;
            foreach ($channels['data'] as $position => $option) {
                if (is_numeric($option['type'])) {
                    $optionLevel = $option['type'];
                } elseif ($option['type'] == '-') {
                    $optionLevel = $optionLevel - 1;
                } elseif ($option['type'] == 'r' || $option['type'] == 's') {
                    $optionLevel = 0;
                }
                if ($optionLevel <= $toLevel) {
                    $subMenu[] = $option;
                    $cant++;
                }
                if ($option['type'] != '-' && $option['type'] != 'o') {
                    ++$optionLevel;
                }
            }
            $channels = ['data' => $subMenu, 'cant' => $cant];
        }
        // set sections open/close according to cookie
        global $prefs;
        foreach ($channels['data'] as $position => &$option) {
            if (! empty($params['menu_cookie']) && $params['menu_cookie'] == 'n') {
                if (! empty($option['selected']) || ! empty($option['selectedAscendant'])) {
                    $option['open'] = true;
                }
            } else {
                if (empty($params['id']) && ! empty($params['structureId'])) {
                    $params['id'] = $params['structureId'];
                }
                $ck = isset($option['position']) ? getCookie('menu' . $params['id'] . '__' . $option['position'], 'menu') : 'o';
                if ($ck === 'o') {
                    $option['open'] = true;
                } elseif ($ck === 'c') {
                    $option['open'] = false;
                }
            }
        }
        return $channels;
    }
    public function lower($subMenu)
    {
        $lower = false;
        foreach ($subMenu as $i => $option) {
            // begin all the secrtion at 0 to have a nice display
            if (is_numeric($option['type'])) {
                if ($lower === false) {
                    $lower = $option['type'];
                }
                $subMenu[$i]['type'] -= $lower;
                if ($subMenu[$i]['type'] == 0) {
                    $subMenu[$i]['type'] = 's';     // section levels go: s, 1, 2, 3 etc
                }
            }
        }
        return $subMenu;
    }

    // check if a option belongs to a menu
    public function check_menu_option($menuId, $optionId)
    {
        $query = 'SELECT `menuId` FROM `tiki_menu_options` WHERE `optionId` = ?';
        $dbMenuId = $this->getOne($query, [$optionId]);
        if ($dbMenuId == $menuId) {
            return true;
        } else {
            return false;
        }
    }

    public function import_menu_options($menuId, $importAsNew = false)
    {
        $smarty = TikiLib::lib('smarty');

        $options = [];
        $fname = $_FILES['csvfile']['tmp_name'];
        $fhandle = fopen($fname, "r");
        $fields = fgetcsv($fhandle, 1000, escape: "");
        if (! $fields[0]) {
            $smarty->assign('msg', tra('The file has incorrect syntax or is not a CSV file'));
            $smarty->display("error.tpl");
            die;
        }
        $mismatch_options = [];
        while (! feof($fhandle)) {
            $res = ['optionId' => '', 'type' => '', 'name' => '', 'url' => '', 'position' => 0, 'section' => '', 'perm' => '', 'groupname' => '', 'userlevel' => '', 'class' => '', 'icon' => '', 'remove' => ''];
            $data = fgetcsv($fhandle, 1000, escape: "");
            if (empty($data)) {
                continue;
            }
            for ($i = 0, $icount_fields = count($fields); $i < $icount_fields; $i++) {
                $res[$fields[$i]] = $data[$i];
            }
            if (empty($res['optionId']) || $this->check_menu_option($menuId, $res['optionId'])) {
                $options[] = $res;
            } else {
                if ($importAsNew) {
                    $res['optionId'] = 0;
                    $options[] = $res;
                } else {
                    $mismatch_options[] = $res;
                }
            }
        }
        fclose($fhandle);

        if (! empty($mismatch_options)) {
            global $jitRequest;
            $jitRequest['mismatch_options'] = $mismatch_options;
            $jitRequest['menuId'] = $menuId;
            $jitRequest['title'] = tr('Menu Import Conflict: Resolve Mismatched Options');
            $controller = 'menu';
            $action = 'mismatch_import';

            $broker = TikiLib::lib('service')->getBroker();
            $broker->process($controller, $action, $jitRequest);
            exit;
        }

        foreach ($options as $option) {
            if ($option['remove'] == 'y') {
                $this->remove_menu_option($option['optionId']);
            } else {
                $this->replace_menu_option($menuId, $option['optionId'], $option['name'], $option['url'], $option['type'], $option['position'], $option['section'], $option['perm'], $option['groupname'], $option['userlevel'], $option['icon'], $option['class']);
            }
        }
    }

    public function export_menu_options($menuId, $encoding)
    {
        $data = '"optionId","type","name","url","position","section","perm","groupname","userlevel","class","icon","remove"' . "\r\n";
        $options = $this->list_menu_options($menuId, 0, -1, 'position_asc', '', true, 0, true);
        $keysToKeep = [
            "optionId", "type", "name", "url", "position", "section",
            "perm", "groupname", "userlevel", "class", "icon"
        ];
        foreach ($options['data'] as $option) {
            $option = array_intersect_key($option, array_flip($keysToKeep));
            $option = array_map(fn($value) => $this->sanitizeForCsv($value), $option);
            $option['remove'] = 'n';
            $data .= $this->str_putcsv($option);
            $data .= "\r\n";
        }
        if (empty($encoding)) {
            $encoding = 'UTF-8';
        } elseif ($encoding == 'ISO-8859-1') {
            $data = mb_convert_encoding($data, 'ISO-8859-1', 'UTF-8');
        }
        header("Content-type: text/comma-separated-values; charset:" . $encoding);
        header("Content-Disposition: attachment; filename=" . tra('menu') . "_" . $menuId . ".csv");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header("Pragma: public");
        echo $data;
        die;
    }
    public function get_option($menuId, $url)
    {
        $query = 'select `optionId` from `tiki_menu_options` where `menuId`=? and `url`=?';
        return $this->getOne($query, [$menuId, $url]);
    }

    public function get_menu($menuId)
    {
        $res = $this->table('tiki_menus')->fetchFullRow(['menuId' => (int) $menuId]);

        if (! is_array($res)) {
            $res = [];
        }

        if (empty($res['icon'])) {
            $res['oicon'] = null;
        } else {
            $res['oicon'] = dirname($res['icon']) . '/o' . basename($res['icon']);
        }
        return $res;
    }

    public function list_menu_options($menuId, $offset = 0, $maxRecords = -1, $sort_mode = 'position_asc', $find = '', $full = false, $level = 0, $do_not_parse = false, $list_all = false)
    {
        global $user, $tiki_p_admin, $prefs;
        $wikilib = TikiLib::lib('wiki');
        include_once('tiki-sefurl.php');

        $options = $this->table('tiki_menu_options');
        $no_editable_page = [];
        $conditions = [
                'menuId' => $menuId,
                ];
        if ($find) {
            $conditions['search'] = $options->expr('(`name` like ? or `url` like ?)', ["%$find%", "%$find%"]);
        }

        if ($level && $prefs['feature_userlevels'] == 'y') {
            $conditions['userlevel'] = $options->lesserThan($level + 1);
        }

        $menu = $this->get_menu($menuId);

        $sort = $options->expr($this->convertSortMode($sort_mode));
        $result = $options->fetchAll($options->all(), $conditions, $maxRecords, $offset, $sort);
        $cant = $options->fetchCount($conditions);

        // ensure page-specific permissions are not affecting menu building
        $globalperms = Perms::get();

        $ret = [];
        foreach ($result as $res) {
            $res['canonic'] = $res['url'];
            $resourceGroups = array_filter(explode(',', $res['groupname'] ?: ''));
            if (! $do_not_parse) {
                if (isset($menu['parse']) && $menu['parse'] === 'y') {
                    $res['name'] = TikiLib::lib('parser')->parse_data($res['name'], ['suppress_icons' => true]);
                } else {
                    $res['name'] = htmlspecialchars($res['name']);
                }
            }
            if (preg_match('|^\(\((.+?)\)\)$|', $res['url'], $matches)) {
                $res['url'] = 'tiki-index.php?page=' . rawurlencode($matches[1]);
                $res['sefurl'] = $wikilib->sefurl($matches[1]);
                $perms = Perms::get(['type' => 'wiki page', 'object' => $matches[1]]);
                if ($list_all) {
                    if (! $perms->view && ! $perms->wiki_view_ref) {
                        $no_editable_page[] = $res['url'];
                    }
                } else {
                    if (! $perms->view && ! $perms->wiki_view_ref) {
                        continue;
                    }
                }
            } else {
                $res['sefurl'] = filter_out_sefurl($res['url']);
            }
            if (! $full) {
                $display = true;
                if (isset($res['section']) and $res['section']) {
                    if (strstr($res['section'], '|')) {
                        $display = false;
                        $sections = preg_split('/\s*\|\s*/', $res['section']);
                        foreach ($sections as $sec) {
                            if (! isset($prefs[$sec]) or $prefs[$sec] != 'y') {
                                $display = true;
                                break;
                            }
                        }
                    } else {
                        $display = true;
                        $sections = preg_split('/\s*,\s*/', $res['section']);
                        foreach ($sections as $sec) {
                            if (! isset($prefs[$sec]) or $prefs[$sec] != 'y') {
                                $display = false;
                                break;
                            }
                        }
                    }
                }
                if ($display && $tiki_p_admin != 'y') {
                    if (isset($res['perm']) and $res['perm']) {
                        if (strstr($res['perm'], '|')) {
                            $display = false;
                            $sections = preg_split('/\s*\|\s*/', $res['perm']);
                            foreach ($sections as $sec) {
                                $sec = str_replace('tiki_p_', '', $sec);
                                if ($globalperms->$sec) {
                                    $display = true;
                                    break;
                                }
                            }
                        } else {
                            $sections = preg_split('/\s*,\s*/', $res['perm']);
                            $display = true;
                            foreach ($sections as $sec) {
                                $sec = str_replace('tiki_p_', '', $sec);
                                if (! $globalperms->$sec) {
                                    $display = false;
                                    break;
                                }
                            }
                        }
                    }
                    $userGroups = $this->get_user_groups($user);
                    if (count($resourceGroups) > 0) {
                        $intersect = array_intersect($resourceGroups, $userGroups);
                        if (count($intersect) < 1) {
                            $display = false;
                        }
                    }
                }
                if ($display) {
                    $pos = $res['position'];
                    if (empty($ret[$pos]) || empty($ret[$pos]['url'])) {
                        $ret[$pos] = $res;
                    }
                }
            } else {
                $ret[] = $res;
            }
        }

        return [
            'data' => array_values($ret),
            'cant' => $cant,
            'no_editable_page' => $no_editable_page,
            ];
    }
    /*
     *gets result from list_menu_options and sorts "sorted section" sections.
     */
    public function sort_menu_options($channels)
    {

        $sorted_channels = [];

        if (! isset($channels['data']) || $channels['cant'] == 0) {
            return $channels;
        }
        $cant = $channels['cant'];
        $channels = $channels['data'];

        $temp_max = count($channels);
        for ($i = 0; $i < $temp_max; $i++) {
            $sorted_channels[$i] = $channels[$i];
            if ($sorted_channels[$i]['type'] == 'r') {
                // sorted section
                $sorted_channels[$i]['type'] = 's'; // common section, let's make it transparent
                $i++;
                $section = [];
                while ($i < count($channels) && $channels[$i]['type'] == 'o') {
                    $section[] = $channels[$i];
                    $i++;
                }
                $i--;
                $functionMenuHandler = new \SmartyTiki\FunctionHandler\Menu();
                usort($section, [$functionMenuHandler, "compareMenuOptions"]);
                $sorted_channels = array_merge($sorted_channels, $section);
            }
        }

        if (isset($cant)) {
            $sorted_channels = ['data' => $sorted_channels,
                    'cant' => $cant];
        }

        return $sorted_channels;
    }

    public static function clean_menu_html($data)
    {
        $data = preg_replace('/<ul>\s*<\/ul>/', '', $data);
        $data = preg_replace('/<ol>\s*<\/ol>/', '', $data);
        return '<nav class="role_navigation">' . $data . '</nav>';
    }
}
