<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class BannerLib extends TikiLib
{
    public function select_banner_id($zone)
    {
        $map = [0 => 'sun', 1 => 'mon', 2 => 'tue', 3 => 'wed', 4 => 'thu', 5 => 'fri', 6 => 'sat'];
        $dw = $map[$this->date_format("%w")];

        $hour = $this->date_format("%H") . $this->date_format("%M");
        $cookieName = "banner_$zone";
        $mid = '';
        $views = [];
        $bindvars = ['y', $hour, $hour, 'y', (int) $this->now, (int) $this->now, 'n', -1, -1, $zone];

        if (isset($_COOKIE[$cookieName])) {
            $views = json_decode($_COOKIE[$cookieName]);
            $mid = 'and (`bannerId` not in (' . implode(',', array_fill(0, count($views), '?')) . ') or ';
            foreach ($views as $bId => $bView) {
                $bindvars[] = $bId;
            }

            foreach ($views as $bId => $bView) {
                $mids[] = '(`bannerId` = ? and `maxUserImpressions` > ?)';
                $bindvars[] = $bId;
                $bindvars[] = $bView;
            }
            $mid .= implode('or', $mids) . ')';
        }

            $query = "select `bannerId` from `tiki_banners` where `$dw` = ? and  `hourFrom`<=? and `hourTo`>=? and" .
                            " ( ((`useDates` = ?) and (`fromDate`<=? and `toDate`>=?)) or (`useDates` = ?) ) and" .
                            " (`impressions`<`maxImpressions`  or `maxImpressions`=?) and" .
                            "  (`clicks`<`maxClicks` or `maxClicks`=? or `maxClicks` is NULL)" .
                            " and `zone`=? $mid and (`exceptInURIs` not like ? or `exceptInURIs` IS NULL)";

            $bindvars[] = '%#' . $_SERVER['REQUEST_URI'] . '#%';
            $query1 = "$query and `onlyInURIs` like ? order by " . $this->convertSortMode('random');

            $result = $this->query($query1, array_merge($bindvars, ['%#' . $_SERVER['REQUEST_URI'] . '#%']), 1, 0);
        if (! ($res = $result->fetchRow())) {
            $query1 = "$query and (`onlyInURIs` is NULL or `onlyInURIs` =?) order by " . $this->convertSortMode('random');
            $bindvars[] = '';
            $result = $this->query($query1, $bindvars, 1, 0);
            if (! ($res = $result->fetchRow())) {
                return false;
            }
        }
            $id = $res["bannerId"];

            // Increment banner impressions here
        if ($id) {
            $query = "update `tiki_banners` set `impressions` = `impressions` + 1 where `bannerId` = ?";
            $result = $this->query($query, [$id]);
        }

            return $id;
    }


    public function select_banner($zone, $target = '_blank', $id = '')
    {
        global $prefs, $tikilib;

        // Things to check
        // UseDates and dates
        // Hours
        // weekdays
        // zone
        // maxImpressions and impressions

        if (! empty($zone)) {
            $id = $this->select_banner_id($zone);
        }
        if (! $id) {
            return '';
        }
        $res = $this->get_banner($id);
        $class = 'banner' . str_replace(' ', '_', $zone);

        $raw = '';

        switch ($res["which"]) {
            case 'useHTML':
                $raw = $res["HTMLData"];

                break;

            case 'useImage':
                $raw
                    = "<div class='banner $class'><a target='$target' href='banner_click.php?id=" . $res["bannerId"] . '\'>' .
                    "<img class='img-fluid' alt='banner' src='banner_image.php?id=" . $res["bannerId"] . "'></a></div>";

                break;

            case 'useFixedURL':
                $raw
                    = "<div class='banner $class'><a target='$target' href='banner_click.php?id=" . $res["bannerId"] . '\'>'
                    . '<img src="' . $res["fixedURLData"] . '" alt="banner" /></a></div>';

                break;

            case 'useText':
                $raw = "<a target='$target' class='bannertext $class' href='banner_click.php?id=" . $res["bannerId"] . "'>" . $res["textData"] . "</a>";

                break;
        }

        // Increment banner impressions done in select_banner_id()
        // Now to set view limiting cookie for user
        $cookieName = "banner_$zone";
        $views = [];
        if (isset($_COOKIE[$cookieName])) {
            $views = json_decode($_COOKIE[$cookieName]);
        }
        if ($res['maxUserImpressions'] > 0) {
            $views[$res['bannerId']] = isset($views[$res['bannerId']]) ? $views[$res['bannerId']] + 1 : 1;
            $expire = $res['useDates'] ? $res['toDate'] : $tikilib->now + 60 * 60 * 24 * 90; //90 days
            setcookie($cookieName, json_encode($views), $expire);
        }

        return $raw;
    }

    public function add_click($bannerId)
    {
        $query = "update `tiki_banners` set `clicks` = `clicks` + 1 where `bannerId`=?";

        $result = $this->query($query, [(int)$bannerId]);
    }

    public function list_banners($offset, $maxRecords, $sort_mode, $find, $user)
    {
        if ($user == 'admin') {
            $mid = '';
            $bindvars = [];
        } else {
            $mid = "where `client` = ?";
            $bindvars = [$user];
        }


        if ($find) {
            $findesc = '%' . $find . '%';
            $bindvars[] = $findesc;

            if ($mid) {
                $mid .= " and `url` like ? ";
            } else {
                $mid .= " where `url` like ? ";
            }
        }

        $query = "select * from `tiki_banners` $mid order by " . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `tiki_banners` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["cant"] = $cant;
        return $retval;
    }

    public function list_zones()
    {
        $query = "select `zone` from `tiki_zones`";

        $query_cant = "select count(*) from `tiki_zones`";
        $result = $this->query($query, []);
        $cant = $this->getOne($query_cant, []);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["cant"] = $cant;
        return $retval;
    }

    public function remove_banner($bannerId)
    {
        $query = "delete from `tiki_banners` where `bannerId`=?";

        $result = $this->query($query, [$bannerId]);
    }

    public function get_banner($bannerId)
    {
        $query = "select * from `tiki_banners` where `bannerId`=?";

        $result = $this->query($query, [$bannerId]);

        if (! $result->numRows()) {
            return false;
        }

        $res = $result->fetchRow();
        return $res;
    }

    public function replace_banner(
        $bannerId,
        $client,
        $url,
        $title,
        $alt,
        $use,
        $imageData,
        $imageType,
        $imageName,
        $HTMLData,
        $fixedURLData,
        $textData,
        $fromDate,
        $toDate,
        $useDates,
        $mon,
        $tue,
        $wed,
        $thu,
        $fri,
        $sat,
        $sun,
        $hourFrom,
        $hourTo,
        $maxImpressions,
        $maxClicks,
        $zone,
        $maxUserImpressions = -1,
        $onlyInURIs = null,
        $exceptInURIs = null
    ) {

        $imageData = urldecode($imageData);
        //$imageData = '';

        if ($bannerId) {
            $query = "update `tiki_banners` set
                `client` = ?,
                `url` = ?,
                `title` = ?,
                `alt` = ?,
                `which` = ?,
                `imageData` = ?,
                `imageType` = ?,
                `imageName` = ?,
                `HTMLData` = ?,
                `fixedURLData` = ?,
                `textData` = ?,
                `fromDate` = ?,
                `toDate` = ?,
                `useDates` = ?,
                `created` = ?,
                `zone` = ?,
                `hourFrom` = ?,
                `hourTo` = ?,
                `mon` = ? ,`tue` = ?, `wed` = ?, `thu` = ?, `fri` = ?, `sat` = ?, `sun` = ?,
                `maxImpressions` = ?, `maxUserImpressions`=?, `maxClicks` = ?, `onlyInURIs`=?, `exceptInURIs`=? where `bannerId`=?";

            $bindvars = [
                                $client,
                                $url,
                                $title,
                                $alt,
                                $use,
                                $imageData,
                                $imageType,
                                $imageName,
                                $HTMLData,
                                $fixedURLData,
                                $textData,
                                $fromDate,
                                $toDate,
                                $useDates,
                                $this->now,
                                $zone,
                                $hourFrom,
                                $hourTo,
                                $mon,
                                $tue,
                                $wed,
                                $thu,
                                $fri,
                                $sat,
                                $sun,
                                $maxImpressions,
                                $maxUserImpressions,
                                $maxClicks,
                                $onlyInURIs,
                                $exceptInURIs,
                                $bannerId
            ];

            $result = $this->query($query, $bindvars);

            /* invalid cache */
            global $tikilib, $tikidomain, $prefs;
            $bannercachefile = $prefs['tmpDir'];
            if ($tikidomain) {
                $bannercachefile .= "/$tikidomain";
            }
            $bannercachefile .= "/banner." . (int)$bannerId;
            unlink($bannercachefile);
        } else {
            $query = "insert into `tiki_banners`(`client`, `url`, `title`, `alt`, `which`, `imageData`, `imageType`, `HTMLData`," .
                            " `fixedURLData`, `textData`, `fromDate`, `toDate`, `useDates`, `mon`, `tue`, `wed`, `thu`, `fri`, `sat`, `sun`," .
                            " `hourFrom`, `hourTo`, `maxImpressions`,`maxUserImpressions`,`maxClicks`,`created`,`zone`,`imageName`," .
                            " `impressions`,`clicks`, `onlyInURIs`, `exceptInURIs`)" .
                            " values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

            $bindvars = [
                                    $client,
                                    $url,
                                    $title,
                                    $alt,
                                    $use,
                                    $imageData,
                                    $imageType,
                                    $HTMLData,
                                    $fixedURLData,
                                    $textData,
                                    $fromDate,
                                    $toDate,
                                    $useDates,
                                    $mon,
                                    $tue,
                                    $wed,
                                    $thu,
                                    $fri,
                                    $sat,
                                    $sun,
                                    $hourFrom,
                                    $hourTo,
                                    $maxImpressions,
                                    $maxUserImpressions,
                                    $maxClicks,
                                    $this->now,
                                    $zone,
                                    $imageName,
                                    0,
                                    0,
                                    $onlyInURIs,
                                    $exceptInURIs
            ];


            $result = $this->query($query, $bindvars);
            $query = "select max(`bannerId`) from `tiki_banners` where `created`=?";
            $bannerId = $this->getOne($query, [(int)$this->now]);
        }

        return $bannerId;
    }

    public function banner_add_zone($zone)
    {
        $query = "delete from `tiki_zones` where `zone`=?";
        $this->query($query, [$zone], -1, -1, false);
        $query = "insert into `tiki_zones`(`zone`) values(?)";
        $result = $this->query($query, [$zone]);
        return true;
    }

    public function banner_get_zones()
    {
        $query = "select * from `tiki_zones`";

        $result = $this->query($query, []);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        return $ret;
    }

    public function banner_remove_zone($zone)
    {
        $query = "delete from `tiki_zones` where `zone`=?";

        $result = $this->query($query, [$zone]);

        return true;
    }
}
