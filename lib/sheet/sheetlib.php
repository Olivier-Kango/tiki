<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// Tikiwiki Sheet Library {{{1

require_once("grid.php");

if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

class SheetLib extends TikiLib
{
    private $setup_jQuery_sheet_files;

    public function get_sheet_info($sheetId) // {{{2
    {
        $result = $this->query("SELECT * FROM `tiki_sheets` WHERE `sheetId` = ?", [ $sheetId ]);
        $result = $result->fetchRow();

        if (! empty($result)) {
            $result['tiki_p_edit_sheet'] = $this->user_can_edit($sheetId);
            $ids = $this->get_related_sheet_ids($sheetId, true);
            $lastId = end($ids);
            $result['parentSheetId'] = $lastId;
            $result['childSheetIds'] = $this->get_related_sheet_ids($sheetId);
            $result['childTrackerIds'] = $this->get_related_tracker_ids($sheetId);
            $result['childFileIds'] = $this->get_related_file_ids($sheetId);
            $result['created'] = $this->get_sheet_created($result['sheetId']);
            $result['lastModif'] = $this->get_lastModif($result['sheetId']);
            if (! $result['lastModif']) {
                if ($this->get_sheet_lastModif($result['sheetId'])) {
                    $result['lastModif'] = $this->get_sheet_lastModif($result['sheetId']);
                } else {
                    $result['lastModif'] = $result['created'];
                }
            }

            return $result;
        }
    }

    public function get_sheet_layout($sheetId) // {{{2
    {
        $result = $this->query("SELECT `className`, `headerRow`, `footerRow`, `parseValues`, `metadata` FROM `tiki_sheet_layout` WHERE `sheetId` = ? AND `end` IS NULL", [ $sheetId ]);

        return $result->fetchRow();
    }

    /**
     * Get sheet values
     *
     * @param int $sheetId
     * @return array
     */
    public function getSheetValue($sheetId) // {{{2
    {
        $result = $this->fetchAll("SELECT `sheetId`, `begin`, `end`, `rowIndex`, `columnIndex`, `value`, `calculation`, `width`, `height`, `format`, `user`, `style`, `class`, `clonedSheetId` FROM `tiki_sheet_values` WHERE `sheetId` = ?", [ $sheetId ]);

        return $result;
    }

    //general relationships management
    public function add_relate($type, $sheetId, $childId)
    {
        $relationlib = TikiLib::lib('relation');
        $relationlib->add_relation("tiki.sheet." . $type, "sheetId", $sheetId, $type . "Id", $childId);
    }

    public function remove_relate($type, $sheetId, $childId)
    {
        $relationlib = TikiLib::lib('relation');
        foreach ($relationlib->get_relations_from("sheetId", $sheetId, "tiki.sheet." . $type) as $result) {
            if ($result['itemId'] == $childId) {
                $relationlib->remove_relation($result['relationId']);
            }
        }
    }

    public function get_relate_all($type, $sheetId, $inverted = false)
    {
        $relationlib = TikiLib::lib('relation');
        $entityIds = [];
        if ($inverted == true) {
            foreach ($relationlib->get_relations_to("sheetId", $sheetId, "tiki.sheet." . $type) as $result) {
                $entityIds[] = $result['itemId'];
            }
        } else {
            foreach ($relationlib->get_relations_from("sheetId", $sheetId, "tiki.sheet." . $type) as $result) {
                $entityIds[] = $result['itemId'];
            }
        }
        return $entityIds;
    }

    public function remove_relate_all($type, $sheetId)
    {
        foreach ($this->get_relate_all($type, $sheetId) as $entityId) {
            $this->remove_related_tracker($sheetId, $entityId);
        }
    }

    public function update_relate($type, $sheetId, $entityIds)
    {
        $this->remove_relate_all($type, $sheetId);

        foreach ($entityIds as $entityId) {
            $this->add_relate($type, $sheetId, $entityId);
        }
    }

    //file relationships
    public function add_related_file($sheetId, $fileId)
    {
        $this->add_relate("file", $sheetId, $fileId);
    }

    public function remove_related_file($sheetId, $fileId)
    {
        $this->remove_relate("file", $sheetId, $fileId);
    }

    public function remove_related_files($sheetId)
    {
        $this->remove_relate_all("file", $sheetId);
    }

    public function get_related_file_ids($sheetId)
    {
        return $this->get_relate_all("file", $sheetId);
    }

    public function update_related_files($sheetId, $fileId)
    {
        $this->update_relate("file", $sheetId, $fileId);
    }

    //tracker relationships
    public function add_related_tracker($sheetId, $trackerId)
    {
        $this->add_relate("tracker", $sheetId, $trackerId);
    }

    public function remove_related_tracker($sheetId, $trackerId)
    {
        $this->remove_relate("tracker", $sheetId, $trackerId);
    }

    public function remove_related_trackers($sheetId)
    {
        $this->remove_relate_all("tracker", $sheetId);
    }

    public function get_related_tracker_ids($sheetId)
    {
        return $this->get_relate_all("tracker", $sheetId);
    }

    public function update_related_trackers($sheetId, $trackerIds)
    {
        $this->update_relate("tracker", $sheetId, $trackerIds);
    }

    //sheet relationships
    public function add_related_sheet($sheetId, $childSheetId)
    {
        $this->remove_related_sheet($sheetId, $childSheetId);

        $this->add_relate("sheet", $sheetId, $childSheetId);
    }

    public function remove_related_sheets($sheetId)
    {
        $this->query(" UPDATE `tiki_sheets` SET `parentSheetId` = 0 WHERE `parentSheetId` = ? ", [ $sheetId ]);
        $this->remove_relate_all("sheet", $sheetId);
    }

    public function remove_related_sheet($childSheetId)
    {
        $this->query(" UPDATE `tiki_sheets` SET `parentSheetId` = 0 WHERE `sheetId` = ? ", [ $childSheetId ]);
        $sheetIds = $this->get_related_sheet_ids($childSheetId, true);
        $sheetId = end($sheetIds);
        $this->remove_relate("sheet", $sheetId, $childSheetId);
    }

    public function update_related_sheets($sheetId, $childSheetIds)
    {
        foreach ($childSheetIds as $childSheetId) {
            $this->remove_related_sheet($sheetId, $childSheetId);
        }

        $this->update_relate("sheet", $sheetId, $childSheetIds);
    }

    public function get_related_sheet_ids($sheetId, $getParent = false) // {{{2
    {
        $sheetIds = [];
        foreach ($this->fetchAll("SELECT `sheetId` FROM `tiki_sheets` WHERE `parentSheetId` = ?", [ $sheetId ]) as $result) {
            $sheetIds[] = $result['sheetId'];
        }

        $sheetIds = array_merge($this->get_relate_all("sheet", $sheetId, $getParent), $sheetIds);

        foreach ($sheetIds as $childSheetId) {
            $sheetIds = array_merge($this->get_relate_all("sheet", $childSheetId, $getParent), $sheetIds);
        }

        return $sheetIds;
    }

    public function list_sheets($offset = 0, $maxRecord = -1, $sort_mode = 'title_desc', $find = '') // {{{2
    {
        global $user;
        $userlib = TikiLib::lib('user');
        $tikilib = TikiLib::lib('tiki');
        switch ($sort_mode) {
            case "author_asc":
                $sort = "`author` ASC";
                break;
            case "author_desc":
                $sort = "`author` DESC";
                break;
            case "description_asc":
                $sort = "`description` ASC";
                break;
            case "description_desc":
                $sort = "`description` DESC";
                break;
            case "title_asc":
                $sort = "`title` ASC";
                break;
            case "title_desc":
                $sort = "`title` DESC";
                break;
            case "sheetId_asc":
                $sort = "`sheetId` ASC";
                break;
            case "sheetId_desc":
                $sort = "`sheetId` DESC";
                break;
            default:
                $sort = "`title` ASC";
                break;
        }
        $bindvars = [];
        $mid = '';
        if (! empty($find)) {
            $bindvars[] = "%$find%";
            if (empty($mid)) {
                $mid = ' WHERE ';
            }
            $mid .= ' `title` like ? ';
        }

        $result = $this->fetchAll("SELECT sheetId FROM `tiki_sheets`  $mid ORDER BY $sort", $bindvars, $maxRecord, $offset);

        $sheets = [];
        foreach ($result as $key => $sheet) {
            $children = [];

            foreach ($this->get_related_sheet_ids($sheet['sheetId']) as $childSheetId) {
                $children[$childSheetId] = $this->get_sheet_info($childSheetId);
            }

            $sheet = $this->get_sheet_info($sheet['sheetId']);

            $sheet['children'] = $children;

            if ($this->user_can_view($sheet['sheetId'])) {
                $sheets[$sheet['sheetId']] = $sheet;
            }
        }
        //print_r($sheets);
        $results = [];

        $results['data'] = $sheets;

        foreach ($results['data'] as $key => $sheet) {
            foreach ($sheet['children'] as $key => $childSheetId) {
                if (! empty($results['data'][$key])) {
                    unset($results['data'][$key]);
                }
            }
        }

        //print_r($results);
        $results['cant'] = $this->getOne("SELECT COUNT(*) FROM `tiki_sheets` $mid", $bindvars);

        return $results;
    }

    public function get_created($sheetId)
    {
        return $this->getOne("
                SELECT begin
                FROM tiki_sheet_values
                WHERE sheetId = ?
                ORDER BY begin ASC
            ", [ $sheetId ]);
    }

    public function get_sheet_created($sheetId)
    {
        return $this->getOne("
                SELECT begin
                FROM tiki_sheet_layout
                WHERE sheetId = ?
                ORDER BY begin ASC
            ", [ $sheetId ]);
    }

    public function get_lastModif($sheetId)
    {
        return $this->getOne("
                SELECT begin
                FROM tiki_sheet_values
                WHERE
                    sheetId = ?
                ORDER BY end DESC
            ", [ $sheetId ]);
    }

    public function get_sheet_lastModif($sheetId)
    {
        return $this->getOne("
                SELECT end
                FROM tiki_sheet_layout
                WHERE
                    sheetId = ?
                ORDER BY end DESC
            ", [ $sheetId ]);
    }

    public function remove_sheet($sheetId) // {{{2
    {
        global $prefs;
        $this->query("DELETE FROM `tiki_sheets` WHERE `sheetId` = ?", [ $sheetId ]);
        $this->query("DELETE FROM `tiki_sheet_values` WHERE `sheetId` = ?", [ $sheetId ]);
        $this->query("DELETE FROM `tiki_sheet_layout` WHERE `sheetId` = ?", [ $sheetId ]);

        $this->remove_related_sheet($sheetId);

        if ($prefs['feature_actionlog'] == 'y') {
            $logslib = TikiLib::lib('logs');
            $logslib->add_action('Removed', $sheetId, 'sheet');
            Feedback::success(tra('The sheet has been successfully deleted.'));
        }
    }

    public function replace_sheet($sheetId, $title, $description, $author, $parentSheetId = 0, $layout = []) // {{{2
    {
        global $prefs;

        if ($sheetId == 0) {
            $query = 'SELECT `sheetId` FROM `tiki_sheets` WHERE `title` = ? AND `description` = ? AND `author` = ?';
            $existingSheetId = $this->getOne($query, [$title, $description, $author]);

            if ($existingSheetId) {
                Feedback::error(tra('A sheet with the same title and description already exists.'));
            } else {
                $this->query("INSERT INTO `tiki_sheets` ( `title`, `description`, `author` ) VALUES( ?, ?, ? )", [ $title, $description, $author ]);

                $sheetId = $this->getOne("SELECT MAX(`sheetId`) FROM `tiki_sheets` WHERE `author` = ?", [ $author ]);
                if ($prefs['feature_actionlog'] == 'y') {
                    $logslib = TikiLib::lib('logs');
                    $query = 'select `sheetId` from `tiki_sheets` where `title`=? and `description`= ? and `author`=?';
                    $id = $this->getOne($query, [$title, $description, $author ]);
                    $logslib->add_action('Created', $id, 'sheet');
                    Feedback::success(tra('The sheet has been successfully created.'));
                }
            }
        } else {
            $this->query("UPDATE `tiki_sheets` SET `title` = ?, `description` = ?, `author` = ? WHERE `sheetId` = ?", [ $title, $description, $author, (int) $sheetId ]);

            $this->query("UPDATE `tiki_sheet_layout` SET `end` = ? WHERE `sheetId` = ?", [time(), $sheetId]);

            Feedback::success(tra('The sheet has been successfully configured.'));
        }

        $layoutDefault = [
            "sheetId" => $sheetId,
            "begin" => time(),
            "headerRow" => 1,
            "footerRow" => 1,
            "className" => '',
            "parseValues" => 'n',
            "clonedSheetId" => 0,
            "metadata" => ''
        ];

        foreach ($layoutDefault as $key => $value) {
            if (empty($layout[$key])) {
                $layout[$key] = $layoutDefault[$key];
            }
        }

        $this->query("INSERT INTO `tiki_sheet_layout` (`sheetId`, `begin`, `headerRow`, `footerRow`, `className`, `parseValues`, `clonedSheetId`, `metadata`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [
            $sheetId,
            $layout["begin"],
            $layout["headerRow"],
            $layout["footerRow"],
            $layout["className"],
            $layout["parseValues"],
            $layout["clonedSheetId"],
            $layout["metadata"]
        ]);

        $this->add_related_sheet($parentSheetId, $sheetId);

        return $sheetId;
    }

    public function set_sheet_title($sheetId, $title)
    {
        if ($sheetId) {
            $this->query("UPDATE `tiki_sheets` SET `title` = ? WHERE `sheetId` = ?", [ $title, $sheetId ]);
        }
    }

    public function setup_jquery_sheet()
    {
        $headerlib = TikiLib::lib('header');
        if (! $this->setup_jQuery_sheet_files) {
            $headerlib
                //core
                ->add_cssfile('vendor_bundled/vendor/jquery/jquery-sheet/jquery.sheet.css')
                ->add_jsfile('vendor_bundled/vendor/jquery/jquery-sheet/jquery.sheet.js')

                //parsers
                ->add_jsfile('vendor_bundled/vendor/jquery/jquery-sheet/parser/formula/formula.js')
                ->add_jsfile('vendor_bundled/vendor/jquery/jquery-sheet/parser/tsv/tsv.js')

                //tiki integration
                ->add_jsfile('lib/sheet/grid.js')

                // plugins
                ->add_jsfile('vendor_bundled/vendor/jquery/jquery-sheet/plugins/jquery.sheet.dts.js')
                ->add_jsfile('vendor_bundled/vendor/jquery/jquery-sheet/plugins/jquery.sheet.advancedfn.js')
                ->add_jsfile('vendor_bundled/vendor/jquery/jquery-sheet/plugins/jquery.sheet.financefn.js')

                ->add_jsfile('vendor_bundled/vendor/jquery/jquery-sheet/plugins/globalize.js')
                ->add_jsfile('vendor_bundled/vendor/jquery/jquery-sheet/plugins/globalize.cultures.js')

                ->add_jsfile('vendor_bundled/vendor/jquery/jquery-sheet/plugins/jquery.nearest.min.js', true)
                ->add_jsfile('vendor_bundled/vendor/jquery/jquery-sheet/plugins/raphael-min.js', true)
                ->add_jsfile('vendor_bundled/vendor/jquery/jquery-sheet/plugins/g.raphael-min.js', true);

            $this->setup_jQuery_sheet_files = true;
        }
    }

    public function sheet_history($sheetId)
    {
        return $this->fetchAll("
            SELECT DISTINCT
                `tiki_sheet_values`.`begin` as stamp,
                `tiki_sheet_values`.`user`,
                DATE_FORMAT(FROM_UNIXTIME(`tiki_sheet_values`.`begin`), '%M %D %Y %h:%i:%s') as prettystamp
            FROM `tiki_sheet_values`
            INNER JOIN `tiki_sheets` ON `tiki_sheets`.`sheetId` = `tiki_sheet_values`.`sheetId`
            WHERE `tiki_sheets`.`sheetId` = ? OR `tiki_sheets`.`parentSheetId` = ?
            ORDER BY begin DESC", [ $sheetId, $sheetId ]);
    }

    public function rollback_sheet($id, $readdate = null)
    {
        global $user, $prefs;

        if ($readdate) {
            $now = (int)time();

             $this->query("
                 UPDATE `tiki_sheet_values`
                 SET `end` = ?
                 WHERE
                     `sheetId` = ? AND
                     `end` IS NULL
             ", [ $now, $id ]);

             $this->query("
                 INSERT INTO `tiki_sheet_values` (`sheetId`, `begin`, `rowIndex`, `columnIndex`, `value`, `calculation`, `width`, `height`, `format`, `user`, `style`, `class`, `clonedSheetId`)
                 SELECT `sheetId`, ?, `rowIndex`, `columnIndex`, `value`, `calculation`, `width`, `height`, `format`, `user`, `style`, `class`, `clonedSheetId`
                 FROM `tiki_sheet_values`
                 WHERE
                     `sheetId` = ? AND
                    ? >= `begin` AND
                    `end` > ?
            ", [ $now, $id, $readdate, $readdate ]);
        }

        if ($prefs['feature_actionlog'] == 'y') {
            $logslib = TikiLib::lib('logs');
            $logslib->add_action('Spreadsheet-Rollback', $id, 'sheet');
        }

        $children = $this->fetchAll("SELECT `sheetId` FROM `tiki_sheets` WHERE `parentSheetId` = ?", [$id]);
        foreach ($children as $child) {
            $this->rollback_sheet($child['sheetId'], $readdate);
        }

        return $id;
    }

    public function clone_sheet($sheetId, $readdate = null, $parentSheetId = 0)
    {
        global $user, $prefs;

        if (! isset($readdate)) {
            $readdate = time();
        }

        $readdate = (int)$readdate;
        $parentSheetId = (int)$parentSheetId;

        //clone the parent sheet & get it's id
        $this->query("
            INSERT INTO `tiki_sheets` (`title`, `description`, `author`, `parentSheetId`, `clonedSheetId`)
            SELECT CONCAT('CLONED - ', `title`), `description`, ?, ?, `sheetid`
            FROM `tiki_sheets`
            WHERE `sheetid` = ?
        ", [ $user, $parentSheetId, $sheetId ]);

        $newSheetId = $this->getOne("SELECT MAX(`sheetId`) FROM `tiki_sheets` WHERE `author` = ?", [ $user ]);
        //clone the sheet layout
        $this->query("
            INSERT INTO `tiki_sheet_layout` (`sheetId`, `begin`, `end`, `headerRow`, `footerRow`, `className`, `parseValues`, `clonedSheetId`)
            SELECT ?, `begin`, `end`, `headerRow`, `footerRow`, `className`, `parseValues`, `sheetId`
            FROM `tiki_sheet_layout`
            WHERE `sheetid` = ?
        ", [ $newSheetId, $sheetId ]);

        //clone sheet's values
        $this->query("
          INSERT INTO `tiki_sheet_values` (`sheetId`, `begin`, `end`, `rowIndex`, `columnIndex`, `value`, `calculation`, `width`, `height`, `format`, `user`, `style`, `class`, `clonedSheetId`)
          SELECT ?, `begin`, NULL, `rowIndex`, `columnIndex`, `value`, `calculation`, `width`, `height`, `format`, `user`, `style`, `class`, ?
          FROM `tiki_sheet_values`
            WHERE
            `sheetId` = ? AND
            ? >= `begin` AND
            (
                `end` IS NULL OR
                `end` > ?
            )
      ", [ $newSheetId, $sheetId, $sheetId, $readdate, $readdate ]);

        //clone the children sheets if they exist
        $result = $this->query("SELECT `sheetId` FROM `tiki_sheets` WHERE `parentSheetId` = ?", [ $sheetId ]);
        while ($row = $result->fetchRow()) {
            if ($row['sheetId']) {
                $this->clone_sheet($row['sheetId'], $readdate, $newSheetId);
            }
        }

        if ($prefs['feature_actionlog'] == 'y') {
            $logslib = TikiLib::lib('logs');
            $logslib->add_action('Cloning', $sheetId, 'sheet');
            $logslib->add_action('Cloned', $newSheetId, 'sheet');
        }

        return $newSheetId;
    }

    public function clone_layout($sheetId, $className, $headerRow, $footerRow, $parseValues = 'n') // {{{2
    {
        if ($row = $this->get_sheet_layout($sheetId)) {
            if (
                $row[ 'className' ] == $className
                && $row[ 'headerRow' ] == $headerRow
                && $row[ 'footerRow' ] == $footerRow
                && $row[ 'parseValues' ] == $parseValues
            ) {
                return true; // No changes have to be made
            }
        }

        $headerRow = empty($headerRow) ? 0 : $headerRow;

        $footerRow = empty($footerRow) ? 0 : $footerRow;

        $stamp = time();

        $this->query("UPDATE `tiki_sheet_layout` SET `end` = ? WHERE sheetId = ? AND `end` IS NULL", [ $stamp, $sheetId ]);
        $this->query(
            "INSERT INTO `tiki_sheet_layout` ( `sheetId`, `begin`, `className`, `headerRow`, `footerRow`, `parseValues` ) VALUES( ?, ?, ?, ?, ?, ? )",
            [ $sheetId, $stamp, $className, (int)$headerRow, (int)$footerRow, $parseValues ]
        );

        return true;
    }

    public function save_sheet($sheets, $sheetId, $layout = [])
    {
        global $user;

        $sheets = json_decode($sheets);

        $rc = '';

        if (! empty($sheetId)) {
            $grid = new TikiSheet();
            if (is_array($sheets)) {
                foreach ($sheets as $sheet) {
                    $handler = new TikiSheetHTMLTableHandler($sheet);
                    $res = $grid->import($handler);
                    // Save the changes
                    $rc .= strlen($rc) === 0 ? '' : ', ';

                    if ($sheet->id != $sheetId) {
                        $sheetIds[] = $sheet->id;
                    }

                    if ($res) {
                        if (! $sheet->id) {
                            if (! empty($sheet->title)) {
                                $title = $sheet->title;
                            } else {
                                $title = $info['title'] . ' subsheet';
                            }

                            $newId = $this->replace_sheet(0, $title, '', $user, $sheetId, $layout);
                            $rc .= tra('new') . " (sheetId=$newId) ";
                            $sheet->id = $newId;
                            $handler = new TikiSheetHTMLTableHandler($sheet);
                            $res = $grid->import($handler);
                        }
                        if ($sheetId && $res) {
                            $handler = new TikiSheetDatabaseHandler($sheet->id, null, json_encode($sheet->metadata));
                            $grid->export($handler);
                            $rc .= $grid->getColumnCount() . ' x ' . $grid->getRowCount() . ' ' . tra('sheet') . " (sheetId=" . $sheet->id . ")";
                        }
                        if (! empty($sheet->title)) {
                            $this->set_sheet_title($sheet->id, $sheet->title);
                        }
                    }
                }
            }
        }
        return ($res ? tra('Saved') . ': ' . $rc : tra('Save failed'));
    }

    /** get_attr_from_css_string {{{2
     * Grabs a css setting from a string
     * @param $style A simple css style string used with an html dom object
     * @param $attr The name of the css attribute you'd like to extract from $style
     */
    public function get_attr_from_css_string($style, $attr, $default)
    {
        $style = strtolower($style);
        $style = str_replace(' ', '', $style);

        $attr = strtolower($attr);

        $cssAttrs = explode(';', $style);
        foreach ($cssAttrs as &$v) {
            $v = explode(':', $v);
        }

        $key = $this->array_searchRecursive($attr, $cssAttrs);
        $result = '';
        if ($key === false) {
            $result = $default;
        } else {
            $result = $cssAttrs[$key[0]][$key[1] + 1];
        }

        return ($result != 'auto' ? $result : $default);
    }

    // array_search with recursive searching, optional partial matches and optional search by key
    public function array_searchRecursive($needle, $haystack, $strict = false, $path = [])
    {
        if (! is_array($haystack)) {
            return false;
        }

        foreach ($haystack as $key => $val) {
            if (is_array($val) && $subPath = $this->array_searchRecursive($needle, $val, $strict, $path)) {
                $path = array_merge($path, [$key], $subPath);
                return $path;
            } elseif ((! $strict && $val == $needle) || ($strict && $val === $needle)) {
                $path[] = $key;
                return $path;
            }
        }
        return false;
    }

    public function diff_sheets_as_html($id, $dates = null)
    {
        global $prefs;

        $count_longest = function ($array1, $array2) {
            $array1 = ! empty($array1) ? $array1 : [];
            $array2 = ! empty($array2) ? $array2 : [];

            return max(count($array1), count($array2));
        };

        $join_with_sub_grids = function ($id, $date) {
            global $prefs;

            $handler = new TikiSheetDatabaseHandler($id, $date);
            $handler->setReadDate($date);
            $grid = new TikiSheet();
            $grid->import($handler);

            $childSheetIds = $this->get_related_sheet_ids($grid->id);
            $i = 0;
            $grids = [$grid];
            foreach ($childSheetIds as $childSheetId) {
                $handler = new TikiSheetDatabaseHandler($childSheetId, $date);
                $handler->setReadDate($date);
                $childSheet = new TikiSheet();
                $childSheet->import($handler);

                array_push($grids, $childSheet);
                $i++;
            }
            return $grids;
        };

        $sanitize_for_diff = function ($val) {
            $val = str_replace("<br/>", "<br>", $val);
            $val = str_replace("<br />", "<br>", $val);
            $val = str_replace("<br  />", "<br>", $val);
            $val = str_replace("<BR/>", "<br>", $val);
            $val = str_replace("<BR />", "<br>", $val);
            $val = str_replace("<BR  />", "<br>", $val);

            return explode("<br>", $val);
        };

        $diff_to_html = function ($changes) use ($count_longest) {
            $result = ["", ""];
            for ($i = 0; $i < $count_longest($changes->orig, $changes->final); $i++) {
                $class = ["", ""];
                $char = ["", ""];
                $vals = [ trim($changes->orig[$i]), trim($changes->final[$i]) ];

                if ($vals[0] && $vals[1]) {
                    if ($vals[0] != $vals[1]) {
                        $class[1] .= "diffadded";
                    }
                } elseif ($vals[0]) {
                    $class[0] .= "diffadded";
                    $class[1] .= "diffdeleted";
                    $vals[1] = $vals[0];
                    $char[1] = "-";
                } elseif ($vals[1]) {
                    $class[0] .= "diffdeleted";
                    $class[1] .= "diffadded";
                    $char[1] = "+";
                }

                if ($vals[0]) {
                    $result[0] .= "<td class='$class[0]'>" . $char[0] . $vals[0] . "</td>";
                }
                if ($vals[1]) {
                    $result[1] .= "<td class='$class[1]'>" . $char[1] . $vals[1] . "</td>";
                }
            }
            return $result;
        };

        $grids1 = $join_with_sub_grids($id, $dates[0]);
        $grids2 = $join_with_sub_grids($id, $dates[1]);

        $result1 = '';
        $result2 = '';

        for ($i = 0; $i < $count_longest($grids1, $grids2); $i++) { //cycle through the sheets within a spreadsheet
            $result1 .= "<table title='" . $grids1[$i]->name() . "'>";
            $result2 .= "<table title='" . $grids2[$i]->name() . "'>";
            for ($row = 0; $row < $count_longest($grids1[$i]->dataGrid, $grids2[$i]->dataGrid); $row++) { //cycle through rows
                $result1 .= "<tr>";
                $result2 .= "<tr>";
                for ($col = 0; $col < $count_longest($grids1[$i]->dataGrid[$row] ?? [], $grids2[$i]->dataGrid[$row] ?? []); $col++) { //cycle through columns
                    $cellValue1 = isset($grids1[$i]->dataGrid[$row][$col]) && is_array($grids1[$i]->dataGrid[$row][$col])
                        ? $grids1[$i]->dataGrid[$row][$col]["value"] ?? ''
                        : $grids1[$i]->dataGrid[$row][$col] ?? '';

                    $cellValue2 = isset($grids2[$i]->dataGrid[$row][$col]) && is_array($grids2[$i]->dataGrid[$row][$col])
                        ? $grids2[$i]->dataGrid[$row][$col]["value"] ?? ''
                        : $grids2[$i]->dataGrid[$row][$col] ?? '';
                    $diff = new Text_Diff($sanitize_for_diff(html_entity_decode($cellValue1)), $sanitize_for_diff(html_entity_decode($cellValue2)));
                    $changes = $diff->getDiff();

                    //I left this diff switch, but it really isn't being used as of now, in the future we may though.
                    switch (get_class($changes[0])) {
                        case 'Text_Diff_Op_copy':
                            $values = $diff_to_html($changes[0]);
                            break;
                        case 'Text_Diff_Op_change':
                            $values = $diff_to_html($changes[0]);
                            break;
                        case 'Text_Diff_Op_delete':
                            $values = $diff_to_html($changes[0]);
                            break;
                        case 'Text_Diff_Op_add':
                            $values = $diff_to_html($changes[0]);
                            break;
                        default:
                            $values = $diff_to_html($changes[0]);
                    }
                    $result1 .= (empty($values[0]) ? '<td></td>' : $values[0]);
                    $result2 .= (empty($values[1]) ? '<td></td>' : $values[1]);
                }
                $result1 .= "</tr>";
                $result2 .= "</tr>";
            }
            $result1 .= "</table>";
            $result2 .= "</table>";
        }

        return [$result1, $result2];
    }

    public function user_can_view($id)
    {
        global $user;
        $objectperms = Perms::get('sheet', $id);
        return ( $objectperms->view_sheet || $objectperms->admin );
    }

    public function user_can_edit($id)
    {
        global $user;
        $objectperms = Perms::get('sheet', $id);
        return ( $objectperms->edit_sheet || $objectperms->admin );
    }
}
