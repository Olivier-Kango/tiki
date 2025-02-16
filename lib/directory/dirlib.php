<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// \todo extract HTML from here !!

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

/**
 *
 */
class DirLib extends TikiLib
{
    // Path functions
    /**
     * @param $categId
     * @return string
     */
    public function dir_get_category_path_admin($categId)
    {
        global $prefs;
        $info = $this->dir_get_category($categId);
        $path = '';
        if ($info) {
            $path = '<a class="link" href="tiki-directory_admin_categories.php?parent=' . $info["categId"] . '">' . $info["name"] . '</a>';
            while ($info["parent"] != 0) {
                $info = $this->dir_get_category($info["parent"]);
                if ($info) {
                    $path = '<a class="link" href="tiki-directory_admin_categories.php?parent=' . $info["categId"] . '">' . $info["name"] . '</a>' . $prefs['site_crumb_seper'] . $path;
                }
            }
        }
        return $path;
    }

    /**
     * @param $categId
     * @return string
     */
    public function dir_get_path_text($categId)
    {
        global $prefs;
        $info = $this->dir_get_category($categId);
        if ($info) {
            $path = $info["name"];
            while ($info["parent"] != 0) {
                $info = $this->dir_get_category($info["parent"]);
                if ($info) {
                    $path = $info["name"] . $prefs['site_crumb_seper'] . $path;
                }
            }
        }
        return $path;
    }

    /**
     * @param $categId
     * @return string
     */
    public function dir_get_category_path_browse($categId)
    {
        global $prefs;
        $path = '';
        $info = $this->dir_get_category($categId);
        if ($info) {
            $path = '<a class="dirlink" href="tiki-directory_browse.php?parent=' . $info["categId"] . '">' . htmlspecialchars($info["name"] ?? "") . '</a>';
            while ($info["parent"] != 0) {
                $info = $this->dir_get_category($info["parent"]);
                if ($info) {
                    $path = '<a class="dirlink" href="tiki-directory_browse.php?parent=' . $info["categId"] . '">' . htmlspecialchars($info["name"] ?? "") . '</a> ' . $prefs['site_crumb_seper'] . ' ' . $path;
                }
            }
        }

        return $path;
    }

    /**
     * @param $categId
     * @return array|null
     */
    public function dir_build_breadcrumb_trail($categId)
    {
        $crumbs = [];
        $info = $this->dir_get_category($categId);
        if (isset($info["name"])) {
            $crumbs[] = new Breadcrumb($info["name"], '', 'tiki-directory_browse.php?parent=' . $info["categId"], '', '');
        }
        while ($info && $info["parent"] != 0) {
            $info = $this->dir_get_category($info["parent"]);
            $crumbs[] = new Breadcrumb($info["name"], '', 'tiki-directory_browse.php?parent=' . $info["categId"], '', '');
        }
        return empty($crumbs) ? null : array_reverse($crumbs);
    }


    // Stats functions
    // get stats (valid sites, invalid sites, categories, searches)

    // Functions to manage categories
    /**
     * @param $parent
     * @param $cant
     * @return array
     */
    public function get_random_subcats($parent, $cant)
    {
        //Return an array of 'cant' random subcategories
        $count = $this->getOne("select count(*) from `tiki_directory_categories` where `parent`=?", [(int)$parent]);
        if ($count < $cant) {
            $cant = $count;
        }
        $ret = [];
        while (count($ret) < $cant) {
            $x = mt_rand(0, $count);
            if (! in_array($x, $ret)) {
                $ret[] = $x;
            }
        }

        $ret = [];
        foreach ($ret as $r) {
            $query = "select * from `tiki_directory_categories`";
            $result = $this->query($query, [], 1, $r);
            $ret[] = $result->fetchRow();
        }
        return $ret;
    }

    // List
    /**
     * @param $parent
     * @param $offset
     * @param $maxRecords
     * @param $sort_mode
     * @param $find
     * @return array
     */
    public function dir_list_categories($parent, $offset, $maxRecords, $sort_mode, $find)
    {
        $bindvars = [(int)$parent];
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " and (`name` like ? or `description` like ?)";
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        } else {
            $mid = "";
        }
        $query = "select * from `tiki_directory_categories` where `parent`=? $mid order by " . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `tiki_directory_categories` where `parent`=? $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res["sites"] = $this->getOne("select count(*) from `tiki_category_sites` where `categId`=?", [(int)$res["categId"]]);

            // TODO : Any permission to check? Used to verify view_categorized when categorized, what is the real permission?
            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["cant"] = $cant;
        return $retval;
    }

    // List all categories
    /**
     * @param $offset
     * @param $maxRecords
     * @param $sort_mode
     * @param $find
     * @return array
     */
    public function dir_list_all_categories($offset, $maxRecords, $sort_mode, $find)
    {
        $bindvars = [];
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " where (`name` like ? or `description` like ?)";
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        } else {
            $mid = "";
        }
        $query = "select * from `tiki_directory_categories` $mid order by " . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `tiki_directory_categories` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res["sites"] = $this->getOne("select count(*) from `tiki_category_sites` where `categId`=?", [(int)$res["categId"]]);
            //$res["path"]=$this->dir_get_path_text($res["categId"]);
            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["cant"] = $cant;
        return $retval;
    }

    /**
     * @param $parent
     * @param int $offset
     * @param $maxRecords
     * @param string $sort_mode
     * @param string $find
     * @param string $isValid
     * @return array
     */
    public function dir_list_sites($parent, $offset = 0, $maxRecords = -1, $sort_mode = 'hits_desc', $find = '', $isValid = 'y')
    {
        $bindvars = [(int)$parent];
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " and (`name` like ? or `description` like ?)";
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        } else {
            $mid = "";
        }
        if ($isValid) {
            $mid .= " and `isValid`=? ";
            $bindvars[] = $isValid;
        }
        $query = "select * from `tiki_directory_sites` tds, `tiki_category_sites` tcs where tds.`siteId`=tcs.`siteId` and tcs.`categId`=? $mid order by " . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `tiki_directory_sites` tds, `tiki_category_sites` tcs where tds.`siteId`=tcs.`siteId` and tcs.`categId`=? $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res["cats"] = $this->dir_get_site_categories($res["siteId"]);
            $res["description"] = TikiLib::lib('parser')->parse_data($res["description"]);
            $ret[] = $res;
        }
        $retval = [];
        $retval["data"] = $ret;
        $retval["cant"] = $cant;
        return $retval;
    }

    /**
     * @param $offset
     * @param $maxRecords
     * @param $sort_mode
     * @param $find
     * @return array
     */
    public function dir_list_invalid_sites($offset, $maxRecords, $sort_mode, $find)
    {
        $bindvars = ["n"];
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " and (`name` like ? or `description` like ?)";
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        } else {
            $mid = "";
        }
        $query = "select * from `tiki_directory_sites` where `isValid`=? $mid order by " . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `tiki_directory_sites` where `isValid`=? $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res["cats"] = $this->dir_get_site_categories($res["siteId"]);
            $ret[] = $res;
        }
        $retval = [];
        $retval["data"] = $ret;
        $retval["cant"] = $cant;
        return $retval;
    }

    /**
     * @param $siteId
     * @return array
     */
    public function dir_get_site_categories($siteId)
    {
        $query = "select tdc.`name`,tcs.`categId` from `tiki_category_sites` tcs,`tiki_directory_categories` tdc where tcs.`siteId`=? and tcs.`categId`=tdc.`categId`";
        $result = $this->query($query, [(int)$siteId]);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res["path"] = $this->dir_get_path_text($res["categId"]);
            $ret[] = $res;
        }
        return $ret;
    }

    /**
     * @param $offset
     * @param $maxRecords
     * @param $sort_mode
     * @param $find
     * @return array
     */
    public function dir_list_all_sites($offset, $maxRecords, $sort_mode, $find)
    {
        $bindvars = [];
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " and (`name` like ? or `description` like ?)";
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        } else {
            $mid = "";
        }
        $query = "select * from `tiki_directory_sites` $mid order by " . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `tiki_directory_sites` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res["cats"] = $this->dir_get_site_categories($res["siteId"]);
            $ret[] = $res;
        }
        $retval = [];
        $retval["data"] = $ret;
        $retval["cant"] = $cant;
        return $retval;
    }

    /**
     * @param $offset
     * @param $maxRecords
     * @param $sort_mode
     * @param $find
     * @return array
     */
    public function dir_list_all_valid_sites($offset, $maxRecords, $sort_mode, $find)
    {
        $bindvars = ['y'];
        $mid = " where `isValid`=? ";
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid .= " and (`name` like ? or `description` like ?)";
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        }

        $query = "select * from `tiki_directory_sites` $mid order by " . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `tiki_directory_sites` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res["cats"] = $this->dir_get_site_categories($res["siteId"]);
            $ret[] = $res;
        }
        $retval = [];
        $retval["data"] = $ret;
        $retval["cant"] = $cant;
        return $retval;
    }

    /**
     * @param $offset
     * @param $maxRecords
     * @param $sort_mode
     * @param $find
     * @param int $siteId
     * @return array
     */
    public function dir_get_all_categories($offset, $maxRecords, $sort_mode, $find, $siteId = 0)
    {
        $bindvars = [];
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " and (`title` like ? or `data` like ?)";
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        } else {
            $mid = "";
        }
        $query = "select * from `tiki_directory_categories` $mid order by " . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `tiki_directory_categories` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res["path"] = $this->dir_get_path_text($res["categId"]);
            $res["belongs"] = 'n';
            if ($siteId) {
                $belongs = $this->getOne("select count(*) from `tiki_category_sites` where `siteId`=? and `categId`=?", [(int)$siteId,(int)$res["categId"]]);
                if ($belongs) {
                    $res["belongs"] = 'y';
                }
            }
            $ret[] = $res;
        }
        usort($ret, 'compare_paths');
        return $ret;
    }

    /**
     * @param $offset
     * @param $maxRecords
     * @param $sort_mode
     * @param $find
     * @param $parent
     * @return array
     */
    public function dir_get_all_categories_np($offset, $maxRecords, $sort_mode, $find, $parent)
    {
        $bindvars = [(int)$parent];
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " and (`title` like ? or `data` like ?)";
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        } else {
            $mid = "";
        }
        $query = "select * from `tiki_directory_categories` where `categId`<>? $mid order by " . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `tiki_directory_categories` where `categId`<>? $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res["path"] = $this->dir_get_path_text($res["categId"]);
            $ret[] = $res;
        }
        usort($ret, 'compare_paths');
        return $ret;
    }

    /**
     * @param $offset
     * @param $maxRecords
     * @param $sort_mode
     * @param $find
     * @param int $siteId
     * @return array
     */
    public function dir_get_all_categories_accept_sites($offset, $maxRecords, $sort_mode, $find, $siteId = 0)
    {
        $bindvars = ['y'];
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " and (`title` like ? or `data` like ?)";
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        } else {
            $mid = "";
        }

        $query = "select * from `tiki_directory_categories` where `allowSites`=? $mid ";
        $query_cant = "select count(*) from `tiki_directory_categories` where `allowSites`=? $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res["sites"] = $this->getOne("select count(*) from `tiki_category_sites` where `categId`=" . $res["categId"]);
            $res["path"] = $this->dir_get_path_text($res["categId"]);
            $res["belongs"] = 'n';

            if ($siteId) {
                $belongs = $this->getOne("select count(*) from `tiki_category_sites` where `siteId`=? and `categId`=?", [(int)$siteId, (int)$res["categId"]]);
                if ($belongs) {
                    $res["belongs"] = 'y';
                }
            }
            $ret[] = $res;
        }
        usort($ret, 'compare_paths');
        return $ret;
    }

    /**
     * @param $siteId
     */
    public function dir_validate_site($siteId)
    {
        $query = "update `tiki_directory_sites` set `isValid`=? where `siteId`=?";
        $this->query($query, ["y", (int)$siteId]);
    }

    /**
     * @param $siteId
     * @param $name
     * @param $description
     * @param $url
     * @param $country
     * @param $isValid
     * @return mixed
     */
    public function dir_replace_site($siteId, $name, $description, $url, $country, $isValid)
    {
        global $prefs;

        $name = TikiFilter::get('striptags')->filter($name);
        $description = TikiFilter::get('striptags')->filter($description);
        $url = TikiFilter::get('url')->filter($url);
        $country = TikiFilter::get('word')->filter($country);

        if ($siteId) {
            $query = "update `tiki_directory_sites` set `name`=?, `description`=?, `url`=?, `country`=?, `isValid`=?, `lastModif`=?  where `siteId`=?";
            $this->query($query, [$name,$description,$url,$country,$isValid,(int)$this->now,(int)$siteId]);
        } else {
            $query = "insert into `tiki_directory_sites`(`name`,`description`,`url`,`country`,`isValid`,`hits`,`created`,`lastModif`) values(?,?,?,?,?,?,?,?)";
            $this->query($query, [$name,$description,$url,$country,$isValid,0,(int)$this->now,(int)$this->now]);
            $siteId = $this->getOne("select max(siteId) from `tiki_directory_sites` where `created`=? and `name`=?", [(int)$this->now,$name]);

            if ($prefs['cachepages'] == 'y') {
                $this->cache_url($url);
            }
        }

        require_once('lib/search/refresh-functions.php');
        refresh_index('directory_sites', $siteId);

        return $siteId;
    }

    // Replace
    /**
     * @param $parent
     * @param $categId
     * @param $name
     * @param $description
     * @param $childrenType
     * @param $viewableChildren
     * @param $allowSites
     * @param $showCount
     * @param $editorGroup
     * @return mixed
     */
    public function dir_replace_category($parent, $categId, $name, $description, $childrenType, $viewableChildren, $allowSites, $showCount, $editorGroup)
    {

        if ($categId) {
            $query = "update `tiki_directory_categories` set `name`=?, `parent`=?, `description`=?, `childrenType`=?, `viewableChildren`=?, `allowSites`=?, `showCount`=?, `editorGroup`=?  where `categId`=?";
            $this->query($query, [$name,(int)$parent,$description,$childrenType,(int)$viewableChildren,$allowSites,$showCount,$editorGroup,(int)$categId]);
        } else {
            $query = "insert into `tiki_directory_categories`(`parent`,`hits`,`name`,`description`,`childrenType`,`viewableChildren`,`allowSites`,`showCount`,`editorGroup`,`sites`) values(?,?,?,?,?,?,?,?,?,?)";
            $this->query($query, [(int)$parent,0,$name,$description,$childrenType,(int)$viewableChildren,$allowSites,$showCount,$editorGroup,0]);
            $categId = $this->getOne("select max(`categId`) from `tiki_directory_categories` where `name`=?", [$name]);
        }

        require_once('lib/search/refresh-functions.php');
        refresh_index('directory_categories', $categId);

        return $categId;
    }

    // Get
    /**
     * @param $siteId
     * @return bool
     */
    public function dir_get_site($siteId)
    {
        $query = "select * from `tiki_directory_sites` where `siteId`=?";
        $result = $this->query($query, [(int)$siteId]);
        if (! $result->numRows()) {
            return false;
        }
        $res = $result->fetchRow();
        return $res;
    }

    /**
     * @param $categId
     * @return bool | array
     */
    public function dir_get_category($categId)
    {
        $query = "select * from `tiki_directory_categories` where `categId`=?";
        $result = $this->query($query, [(int)$categId]);
        if (! $result->numRows()) {
            return false;
        }
        $res = $result->fetchRow();
        return $res;
    }

    /**
     * @param $siteId
     */
    public function dir_remove_site($siteId)
    {
        $query = "delete from `tiki_directory_sites` where `siteId`=?";
        $this->query($query, [(int)$siteId]);
        $query = "delete from `tiki_category_sites` where `siteId`=?";
        $this->query($query, [(int)$siteId]);
    }

    /**
     * @param $siteId
     * @param $categId
     */
    public function dir_add_site_to_category($siteId, $categId)
    {
        $query = "delete from `tiki_category_sites` where `siteId`=? and `categId`=?";
        $this->query($query, [(int)$siteId,(int)$categId]);
        $query = "insert into `tiki_category_sites`(`siteId`,`categId`) values(?,?)";
        $this->query($query, [(int)$siteId,(int)$categId]);
    }

    /**
     * @param $siteId
     */
    public function remove_site_from_categories($siteId)
    {
        $query = "delete from `tiki_category_sites` where `siteId`=?";
        $this->query($query, [(int)$siteId]);
    }

    /**
     * @param $siteId
     * @param $categId
     */
    public function remove_site_from_category($siteId, $categId)
    {
        $query = "delete from `tiki_category_sites` where `siteId`=? and `categId`=?";
        $this->query($query, [(int)$siteId,(int)$categId]);
    }

    /**
     * @param $categId
     */
    public function dir_remove_category($categId)
    {
        $parent_categId = $categId;
        $query = "select * from `tiki_directory_categories` where `parent`=?";
        $result = $this->query($query, [(int)$categId]);

        while ($res = $result->fetchRow()) {
            $categId = $res["categId"];
            $this->dir_remove_category($res["categId"]);

            $query2 = "select * from `tiki_category_sites` where `categId`=?";
            $result2 = $this->query($query2, [(int)$categId]);

            while ($res2 = $result2->fetchRow()) {
                $siteId = $res2["siteId"];
                $query3 = "delete from `tiki_category_sites` where `siteId`=? and `categId`=?";
                $result3 = $this->query($query3, [(int)$siteId,(int)$categId]);
                $cant = $this->getOne("select count(*) from `tiki_category_sites` where `siteId`=?", [(int)$siteId]);
                if (! $cant) {
                    $this->dir_remove_site($siteId);
                }
            }
            $query4 = "delete from `tiki_related_categories` where `categId`=? or `relatedTo`=?";
            $result4 = $this->query($query4, [(int)$categId,(int)$categId]);
        }
        $query = "delete from `tiki_directory_categories` where `categId`=?";
        $result = $this->query($query, [(int)$parent_categId]);
        $query = "delete from `tiki_category_sites` where `categId`=?";
        $result = $this->query($query, [(int)$parent_categId]);
    }

    /**
     * @param $parent
     * @param $related
     */
    public function dir_remove_related($parent, $related)
    {
        $query = "delete from `tiki_related_categories` where `categId`=? and `relatedTo`=?";
        $this->query($query, [(int)$parent,(int)$related]);
    }

    /**
     * @param $parent
     * @param $offset
     * @param $maxRecords
     * @param $soet_mode
     * @param $find
     * @return array
     */
    public function dir_list_related_categories($parent, $offset, $maxRecords, $soet_mode, $find)
    {
        $query = "select * from `tiki_related_categories` where `categId`=?";
        $query_cant = "select count(*) from `tiki_related_categories` where `categId`=?";
        $result = $this->query($query, [(int)$parent], $maxRecords, $offset);
        $cant = $this->getOne($query_cant, [(int)$parent]);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res["path"] = $this->dir_get_path_text($res["relatedTo"]);
            $ret[] = $res;
        }
        $retval = [];
        usort($ret, 'compare_paths');
        $retval["data"] = $ret;
        $retval["cant"] = $cant;
        return $retval;
    }

    /**
     * @param $parent
     * @param $categ
     */
    public function dir_add_categ_rel($parent, $categ)
    {
        $query = "delete from `tiki_related_categories` where `categId`=? and `relatedTo`=?";
        $this->query($query, [(int)$parent,(int)$categ]);
        $query = "insert into `tiki_related_categories`(`categId`,`relatedTo`) values(?,?)";
        $this->query($query, [(int)$parent,(int)$categ]);
    }

    /**
     * @param $url
     * @return mixed
     */
    public function dir_url_exists($url)
    {
        $cant = $this->getOne("select count(*) from `tiki_directory_sites` where `url`=?", [$url]);
        return $cant;
    }

    /**
     * @param $siteId
     */
    public function dir_add_site_hit($siteId)
    {
        global $prefs, $user;
        if (StatsLib::is_stats_hit()) {
            $query = "update `tiki_directory_sites` set `hits`=`hits`+1 where `siteId`=?";
            $this->query($query, [(int)$siteId]);
        }
    }

    /**
     * @param $categId
     */
    public function dir_add_category_hit($categId)
    {
        global $prefs, $user;
        if (StatsLib::is_stats_hit()) {
            $query = "update `tiki_directory_categories` set `hits`=`hits`+1 where `categId`=?";
            $this->query($query, [(int)$categId]);
        }
    }

    /**
     * @param $words
     * @param string $how
     * @param int $offset
     * @param $maxRecords
     * @param string $sort_mode
     * @return array
     */
    public function dir_search($words, $how = 'or', $offset = 0, $maxRecords = -1, $sort_mode = 'hits_desc')
    {
        // First of all split the words by whitespaces building the query string
        // we'll search by name, url, description and cache, the relevance will be calculated using hits
        $words = explode(' ', $words);

        $bindvars = ['y'];
        for ($i = 0, $icount_words = count($words); $i < $icount_words; $i++) {
            $word = trim($words[$i]);
            if (! empty($word)) {
                // Check if the term is in the stats then add it or increment it
                if ($this->getOne("select count(*) from `tiki_directory_search` where `term`=?", [$word])) {
                    $query = "update `tiki_directory_search` set `hits`=`hits`+1 where `term`=?";
                    $this->query($query, [$word]);
                } else {
                    $query = "insert into `tiki_directory_search`(`term`,`hits`) values(?,?)";
                    $this->query($query, [$word,1]);
                }
            }
            $like[$i] = " ((`name` like ?) or (`description` like ?) or (`url` like ?) or (`cache` like ?)) ";
            $bindvars[] = "%$word%";
            $bindvars[] = "%$word%";
            $bindvars[] = "%$word%";
            $bindvars[] = "%$word%";
        }

        $how = in_array($how, ['or', 'and']) ? $how : 'or';
        $likestr = implode($how, $like);
        $query = "select * from `tiki_directory_sites` where `isValid`=? and $likestr  order by "
            . $this->convertSortMode($sort_mode);
        $cant = $this->getOne("select count(*) from tiki_directory_sites where `isValid`=? and $likestr", $bindvars);
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $ret = [];
        while ($res = $result->fetchRow()) {
            $res["cats"] = $this->dir_get_site_categories($res["siteId"]);
            $ret[] = $res;
        }
        $retval = [];
        $retval["data"] = $ret;
        $retval["cant"] = $cant;
        return $retval;
    }

    /**
     * @param $parent
     * @param $words
     * @param string $how
     * @param int $offset
     * @param $maxRecords
     * @param string $sort_mode
     * @return array
     */
    public function dir_search_cat($parent, $words, $how = 'or', $offset = 0, $maxRecords = -1, $sort_mode = 'hits_desc')
    {
        // First of all split the words by whitespaces building the query string
        // we'll search by name, url, description and cache, the relevance will be calculated using hits
        $words = explode(' ', $words);
        $bindvars = ['y',(int)$parent];
        for ($i = 0, $icount_words = count($words); $i < $icount_words; $i++) {
            $word = trim($words[$i]);
            // Check if the term is in the stats then add it or increment it
            if ($this->getOne("select count(*) from `tiki_directory_search` where `term`=?", [$word])) {
                $query = "update `tiki_directory_search` set `hits`=`hits`+1 where `term`=?";
                $this->query($query, [$word]);
            } else {
                $query = "insert into `tiki_directory_search`(`term`,`hits`) values(?,?)";
                $this->query($query, [$word,1]);
            }
            $like[$i] = " ((tds.`name` like ?) or (tds.`description` like ?) or (tds.`url` like ?) or (`cache` like ?)) ";
            $bindvars[] = "%$word%";
            $bindvars[] = "%$word%";
            $bindvars[] = "%$word%";
            $bindvars[] = "%$word%";
        }

        $how = in_array($how, ['or', 'and']) ? $how : 'or';
        $likestr = implode($how, $like);
        $query = "select distinct tds.`name`, tds.`siteId`, tds.`description`, tds.`url`, tds.`country`, tds.`hits`, ";
        $query .= " tds.`created`, tds.`lastModif` from `tiki_directory_sites` tds, `tiki_category_sites` tcs,
            `tiki_directory_categories` tdc ";
        $query .= " where tds.`siteId`=tcs.`siteId` and tcs.`categId`=tdc.`categId` and `isValid`=? and tdc.`categId`=?
            and $likestr order by " . $this->convertSortMode($sort_mode);
        $cant = $this->getOne(
            "select count(*) from `tiki_directory_sites` tds,`tiki_category_sites` tcs,`tiki_directory_categories` tdc
            where tds.`siteId`=tcs.`siteId` and tcs.`categId`=tdc.`categId` and `isValid`=? and tdc.`categId`=?
            and $likestr",
            $bindvars
        );
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $ret = [];
        while ($res = $result->fetchRow()) {
            $res["cats"] = $this->dir_get_site_categories($res["siteId"]);
            $ret[] = $res;
        }
        $retval = [];
        $retval["data"] = $ret;
        $retval["cant"] = $cant;
        return $retval;
    }
}
$dirlib = new DirLib();

/**
 * @param $p1
 * @param $p2
 * @return int
 */
function compare_paths($p1, $p2)
{
        // must be case insentive to have the same than dir_mist_sites
    return strcasecmp($p1["path"], $p2["path"]);
}
