<?php
/* 
V3.60 16 June 2003  (c) 2000-2003 John Lim (jlim@natsoft.com.my). All rights reserved.
  Released under both BSD license and Lesser GPL library license. 
  Whenever there is any discrepancy between the two licenses, 
  the BSD license will take precedence. 
  Set tabs to 4 for best viewing.
	
  Latest version is available at http://php.weblogs.com/
*/

error_reporting(E_ALL);

include_once('../adodb-pear.inc.php');
$username = 'root';
$password = '';
$hostname = 'localhost';
$databasename = 'xphplens';
$driver = 'mysql';

$dsn = "$driver://$username:$password@$hostname/$databasename";

$db = DB::Connect($dsn);
$db->setFetchMode(ADODB_FETCH_ASSOC);
$rs = $db->Query('select firstname,lastname from adoxyz');
$cnt = 0;
while ($arr = $rs->FetchRow()) {
	print_r($arr);
	print "<br>";
	$cnt += 1;
}

if ($cnt != 50) print "<b>Error in \$cnt = $cnt</b>";
?>