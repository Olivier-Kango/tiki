<?php
// $Header: /cvsroot/tikiwiki/tiki/tiki-file_galleries_rss.php,v 1.22 2004-03-28 07:32:23 mose Exp $

// Copyright (c) 2002-2004, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once ('tiki-setup.php');
require_once ('lib/tikilib.php');

if ($rss_file_galleries != 'y') {
        $errmsg=tra("rss feed disabled");
        require_once ('tiki-rss_error.php');
}

if ($tiki_p_view_file_gallery != 'y') {
        $errmsg=tra("Permission denied you cannot view this section");
        require_once ('tiki-rss_error.php');
}

$feed = "filegals";
$title = tra("Tiki RSS feed for file galleries");
$desc = tra("Last files uploaded to the file galleries.");
$now = date("U");
$id = "fileId";
$descId = "description";
$dateId = "created";
$titleId = "filename";
$readrepl = "tiki-download_file.php?$id=";
$uniqueid = $feed;

require ("tiki-rss_readcache.php");

if ($output == "EMPTY") {
  $changes = $tikilib->list_files(0, $max_rss_file_galleries, $dateId.'_desc', '');
  $output="";
}

require ("tiki-rss.php");

?>		
