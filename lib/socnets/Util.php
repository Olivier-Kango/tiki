<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
// author : aris002@yahoo.co.uk

namespace TikiLib\Socnets\Util;

//require_once('lib/prefs/sochybrid.php');
/*
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
	header('location: index.php');
	exit;
}
*/
use TikiLib;


//this is a universal helper/logger - do not put anything socnets specific
class Util
{

  public static string $logfile = 'tikihybrid3.log';
 // public static string $logfile = 'arilect-com_443.error_log';
  public static string $msgPreffix = 'aris002: ';

  
  public static function getLogFile()
  {
    //SHIT
    //return pathinfo(ini_get('error_log'),PATHINFO_DIRNAME) . self::$logfile;
    //return dirname(ini_get('error_log')) . self::$logfile;
    //return $custom_error_log_location . self::$logfile;
    return TIKI_PATH . '/temp/' . self::$logfile;
  }
  
  //TODO would this work with all things static?
  public static function setLogFile($logfile)
  {
      self::$logfile = $logfile;
  }

  //this method had been taken from https://stackoverflow.com/questions/1459739/php-serverhttp-host-vs-serverserver-name-am-i-understanding-the-ma
  public static function getBaseUrl( $array = false )
  {
    $protocol = "";
    $host = "";
    $port = "";
    $dir = "";

    // Get protocol
    if(array_key_exists("HTTPS", $_SERVER) && $_SERVER["HTTPS"] != "") {
      if($_SERVER["HTTPS"] == "on") { $protocol = "https"; }
      else { $protocol = "http"; }
    } elseif(array_key_exists("REQUEST_SCHEME", $_SERVER) && $_SERVER["REQUEST_SCHEME"] != "") { $protocol = $_SERVER["REQUEST_SCHEME"]; }

  // Get host
  if(array_key_exists("HTTP_X_FORWARDED_HOST", $_SERVER) && $_SERVER["HTTP_X_FORWARDED_HOST"] != "") { $host = trim(end(explode(',', $_SERVER["HTTP_X_FORWARDED_HOST"]))); }
  elseif(array_key_exists("SERVER_NAME", $_SERVER) && $_SERVER["SERVER_NAME"] != "") { $host = $_SERVER["SERVER_NAME"]; }
  elseif(array_key_exists("HTTP_HOST", $_SERVER) && $_SERVER["HTTP_HOST"] != "") { $host = $_SERVER["HTTP_HOST"]; }
  elseif(array_key_exists("SERVER_ADDR", $_SERVER) && $_SERVER["SERVER_ADDR"] != "") { $host = $_SERVER["SERVER_ADDR"]; }
  //elseif(array_key_exists("SSL_TLS_SNI", $_SERVER) && $_SERVER["SSL_TLS_SNI"] != "") { $host = $_SERVER["SSL_TLS_SNI"]; }

  // Get port
  if(array_key_exists("SERVER_PORT", $_SERVER) && $_SERVER["SERVER_PORT"] != "") { $port = $_SERVER["SERVER_PORT"]; }
  elseif(stripos($host, ":") !== false) { $port = substr($host, (stripos($host, ":")+1)); }
  // Remove port from host
  $host = preg_replace("/:\d+$/", "", $host);

  // Get dir
  if(array_key_exists("SCRIPT_NAME", $_SERVER) && $_SERVER["SCRIPT_NAME"] != "") { $dir = $_SERVER["SCRIPT_NAME"]; }
  elseif(array_key_exists("PHP_SELF", $_SERVER) && $_SERVER["PHP_SELF"] != "") { $dir = $_SERVER["PHP_SELF"]; }
  elseif(array_key_exists("REQUEST_URI", $_SERVER) && $_SERVER["REQUEST_URI"] != "") { $dir = $_SERVER["REQUEST_URI"]; }
  // Shorten to main dir
  if(stripos($dir, "/") !== false) { $dir = substr($dir, 0, (strripos($dir, "/")+1)); }

  // Create return value
  if(!$array) {
      if($port == "80" || $port == "443" || $port == "") { $port = ""; }
      else { $port = ":".$port; }
      return htmlspecialchars($protocol."://".$host.$port.$dir, ENT_QUOTES);
  } else { return ["protocol" => $protocol, "host" => $host, "port" => $port, "dir" => $dir]; }

}

//should we make a param to exclude index.php and certain files?
public static function getFileNamesPHP($path)
{
    $fileNames = [];
    foreach ( glob($path) as $file)
    {
      if (basename($file) === "index.php") {
        continue;
      }
      $fileNames [] = substr(basename($file), 0, -4);
      // or this way more universal? strtok( basename($file), '.' );

    }
  return $fileNames;
}

//TODO does this work?
public static function deletePrefsStarts($nameStarts = '')
{
  	$tikiLib = TikiLib::lib('tiki');
  global $prefs;

  $ret = [];
  foreach (array_keys($prefs) as $prefName) {
    if (substr($prefName, 0, strlen($nameStarts)) == $nameStarts) {
      $tikiLib->delete_preference($prefName);
      $prefs[$prefName]= null;
      $ret[] = $prefName;
    }
  }

  self::log2('deleted prXXXX: ', $ret);

  return $ret;
}


public static function logclear()
{
  file_put_contents(self::getLogFile(), 'deleted from libs/socnets/Util' . PHP_EOL);
}

public static function log($msg)
{
  $msg = self::$msgPreffix . $msg . PHP_EOL;

  file_put_contents(self::getLogFile(), $msg, FILE_APPEND);
}

  public static function log2($msg, $msg1 = null)
  {
    if (isset($msg1))
      self::log($msg . PHP_EOL . var_export($msg1, true) );
    else
      self::log( $msg  );
  }


}

