<?php
// CVS: $Id: mod-eph.php,v 1.7 2005-08-29 03:14:45 mose Exp $
//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"],basename(__FILE__)) !== false) {
  header("location: index.php");
  exit;
}

include_once ("lib/ephemerides/ephlib.php");

if (isset($_SESSION['thedate'])) {
	$modephpdate = $_SESSION['thedate'];
} else {
	$modephpdate = date("U");
}

$channels = $ephlib->list_eph(0, -1, 'title_desc', '', $modephpdate);

if (count($channels['data'])) {
	$modephpick = rand(0, count($channels['data']) - 1);

	$modephdata = $channels['data'][$modephpick];
} else {
	$modephdata = '';
}

$smarty->assign('modephdata', $modephdata);

?>
