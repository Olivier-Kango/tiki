<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/*
About the design:
tiki-check.php is designed to run in 2 modes
1) Regular mode. From inside Tiki, in Admin | General
2) Stand-alone mode. Used to check a server pre-Tiki installation, by copying (only) tiki-check.php onto the server and pointing your browser to it.
tiki-check.php should not crash but rather avoid running tests which lead to tiki-check crashes.

IMPORTANT:
1) Be careful, this file will copied to past branches as-is, so it needs to run on the oldest php version that supported tiki versions managed by this tool runs. As of 2023-05-18, it is Tiki 18, and thus PHP 7.2
*/

// Disable the following PHPCS checks. tiki-check.php is shared across tiki versions, so may refer to old software
// phpcs:disable PHPCompatibility.Extensions.RemovedExtensions
// phpcs:disable PHPCompatibility.FunctionUse.RemovedFunctions.mysql_queryDeprecatedRemoved
// phpcs:disable PHPCompatibility.FunctionUse.RemovedFunctions.mysql_fetch_arrayDeprecatedRemoved
// phpcs:disable PHPCompatibility.FunctionUse.RemovedFunctions.mysql_connectDeprecatedRemoved
// phpcs:disable PHPCompatibility.IniDirectives.RemovedIniDirectives.mbstring_func_overloadDeprecated

use Tiki\Lib\Alchemy\AlchemyLib;
use Tiki\Lib\Unoconv\UnoconvLib;
use Tiki\Package\ComposerManager;

// TODO : Create sane 3rd mode for Monitoring Software like Nagios, Icinga, Shinken
// * needs authentication, if not standalone
isset($_REQUEST['nagios']) ? $nagios = true : $nagios = false;
file_exists('tiki-check.php.lock') ? $locked = true : $locked = false;
$font = 'lib/captcha/DejaVuSansMono.ttf';

$inputConfiguration = array(
    array(
        'staticKeyFilters' => array(
            'dbhost' => 'text',
            'dbuser' => 'text',
            'dbpass' => 'text',
            'email_test_to' => 'email',
        ),
    ),
);

// reflector for SefURL check
if (isset($_REQUEST['tiki-check-ping'])) {
    die('pong:' . (int)$_REQUEST['tiki-check-ping']);
}

function checkOPcacheCompatibility()
{
    return ! ((version_compare(PHP_VERSION, '7.1.0', '>=') && version_compare(PHP_VERSION, '7.2.0', '<')) //7.1.x
        || (version_compare(PHP_VERSION, '7.2.0', '>=') && version_compare(PHP_VERSION, '7.2.19', '<')) // >= 7.2.0 < 7.2.19
        || (version_compare(PHP_VERSION, '7.3.0', '>=') && version_compare(PHP_VERSION, '7.3.6', '<'))); // >= 7.3.0 < 7.3.6
}

function getTikiRequirements()
{
    return array(
        array(
            'name'    => 'Tiki 28.x',
            'version' => 28,
            'php'     => array(
                'min' => '8.1.0', // For the latest version, this should match TIKI_MIN_PHP_VERSION, but cannot use it since tiki-check is expected to run standalone
                'max' => '8.4.99', // For the latest version, this should match TIKI_MAX_SUPPORTED_PHP_VERSION, but cannot use it since tiki-check is expected to run standalone
            ),
            'mariadb' => array(
                'min' => '10.5',
                'max' => null
            ),
            'mysql'   => array(
                'min' => '8.0',
                'max' => null
            ),
        ),
        array(
            'name'    => 'Tiki 27.x',
            'version' => 27,
            'php'     => array(
                'min' => '8.1.0',
                'max' => '8.4.99',
            ),
            'mariadb' => array(
                'min' => '10.5',
                'max' => null
            ),
            'mysql'   => array(
                'min' => '8.0',
                'max' => null
            ),
        ),
        array(
            'name'    => 'Tiki 26.x',
            'version' => 26,
            'php'     => array(
                'min' => '8.1',
                'max' => '8.2.99',
            ),
            'mariadb' => array(
                'min' => '5.5',
                'max' => null
            ),
            'mysql'   => array(
                'min' => '5.7',
                'max' => null
            ),
        ),
        array(
            'name'    => 'Tiki 25.x',
            'version' => 25,
            'php'     => array(
                'min' => '7.4',
                'max' => '7.4.99',
            ),
            'mariadb' => array(
                'min' => '5.5',
                'max' => null
            ),
            'mysql'   => array(
                'min' => '5.7',
                'max' => null
            ),
        ),
        array(
            'name'    => 'Tiki 24.x',
            'version' => 24,
            'php'     => array(
                'min' => '7.4',
                'max' => '7.4.99',
            ),
            'mariadb' => array(
                'min' => '5.5',
                'max' => null
            ),
            'mysql'   => array(
                'min' => '5.7',
                'max' => null
            ),
        ),
        array(
            'name'    => 'Tiki 23.x',
            'version' => 23,
            'php'     => array(
                'min' => '7.4',
                'max' => '7.4.99',
            ),
            'mariadb' => array(
                'min' => '5.5',
                'max' => null
            ),
            'mysql'   => array(
                'min' => '5.7',
                'max' => null
            ),
        ),
        array(
            'name'    => 'Tiki 22.x',
            'version' => 22,
            'php'     => array(
                'min' => '7.4',
                'max' => '7.4.99',
            ),
            'mariadb' => array(
                'min' => '5.5',
                'max' => null
            ),
            'mysql'   => array(
                'min' => '5.7',
                'max' => null
            ),
        ),
        array(
            'name'    => 'Tiki 21.x LTS',
            'version' => 21,
            'php'     => array(
                'min' => '7.2',
                'max' => '7.3.99',
            ),
            'mariadb' => array(
                'min' => '5.5',
                'max' => null
            ),
            'mysql'   => array(
                'min' => '5.7',
                'max' => null
            ),
        ),
        array(
            'name'    => 'Tiki 20.x',
            'version' => 20,
            'php'     => array(
                'min' => '7.1',
                'max' => '7.2.99',
            ),
            'mariadb' => array(
                'min' => '5.5',
                'max' => '10.4.99',
            ),
            'mysql'   => array(
                'min' => '5.5.3',
                'max' => '5.7.99',
            ),
        ),
        array(
            'name'    => 'Tiki 19.x',
            'version' => 19,
            'php'     => array(
                'min' => '7.1',
                'max' => '7.2.99',
            ),
            'mariadb' => array(
                'min' => '5.5',
                'max' => '10.4.99',
            ),
            'mysql'   => array(
                'min' => '5.5.3',
                'max' => '5.7.99',
            ),
        ),
        array(
            'name'    => 'Tiki 18.x',
            'version' => 18,
            'php'     => array(
                'min' => '5.6',
                'max' => '7.2.99',
            ),
            'mariadb' => array(
                'min' => '5.1',
                'max' => '10.4.99',
            ),
            'mysql'   => array(
                'min' => '5.0',
                'max' => '5.7.99',
            ),
        )
    );
}

function checkServerRequirements($phpVersion, $dbEngine, $dbVersion)
{
    $dbEnginesLabels = array(
        'mysql'   => 'MySQL',
        'mariadb' => 'MariaDB',
    );

    $tikiRequirements = getTikiRequirements();

    $phpValid = false;
    $dbValid = false;

    foreach ($tikiRequirements as $requirement) {
        if (version_compare($phpVersion, $requirement['php']['min'], '<')) {
            continue;
        }
        if (
            isset($requirement['php']['max'])
            && version_compare($phpVersion, $requirement['php']['max'], '>')
        ) {
            continue;
        } else {
        }
        $phpValid = true;
        break;
    }

    $tiki_server_req['PHP'] = array(
        'value'   => PHP_VERSION,
        'fitness' => tra('good'),
        'message' => tra('PHP version is supported by one of Tiki versions'),
    );

    if (! $phpValid) {
        $tiki_server_req['PHP']['fitness'] = tra('bad');
        $tiki_server_req['PHP']['message'] = tra('PHP version is not supported by Tiki');
    }

    if ($dbEngine && $dbVersion) {
        foreach ($tikiRequirements as $tikiVersion) {
            if (version_compare($dbVersion, $tikiVersion[$dbEngine]['min'], '<')) {
                continue;
            }
            if (
                isset($requirement[$dbEngine]['max'])
                && $requirement[$dbEngine]['max'] !== $requirement[$dbEngine]['min']
                && version_compare($dbVersion, $requirement[$dbEngine]['max'], '>')
            ) {
                continue;
            }
            $dbValid = true;
            break;
        }

        $tiki_server_req['Database'] = array(
            'value'   => $dbEnginesLabels[$dbEngine] . ' ' . $dbVersion,
            'fitness' => tra('good'),
            'message' => tra('Database version is supported by one of Tiki Versions.'),
        );

        if (! $dbValid) {
            $tiki_server_req['Database']['fitness'] = tra('bad');
            $tiki_server_req['Database']['message'] = tra('Database version is not supported by Tiki.');
        }
    } else {
        $tiki_server_req['Database'] = array(
            'value'   => 'N/A',
            'fitness' => tra('unsure'),
            'message' => tra('Unable to determine database compatibility'),
        );
    }

    return $tiki_server_req;
}

/**
 * @param string $dbEngine
 * @param string $dbVersion
 *
 * @return array
 */
function getCompatibleVersions($dbEngine = '', $dbVersion = '')
{
    $tikiRequirements = getTikiRequirements();
    $compatibleVersions = array();

    $dbVersion = $dbVersion ?: 0;
    foreach ($tikiRequirements as $requirement) {
        if (version_compare(PHP_VERSION, $requirement['php']['min'], '<')) {
            continue;
        }

        if (
            isset($requirement['php']['max'])
            && $requirement['php']['max'] !== $requirement['php']['min']
            && version_compare(PHP_VERSION, $requirement['php']['max'], '>')
        ) {
            continue;
        }

        if ($dbEngine === 'mysql' || $dbEngine === 'mariadb') {
            if (version_compare($dbVersion, $requirement[$dbEngine]['min'], '<')) {
                continue;
            }
            if (
                isset($requirement[$dbEngine]['max'])
                && version_compare($dbVersion, $requirement[$dbEngine]['max'], '>')
            ) {
                continue;
            }
        }

        $requirement['fitness'] = tra('unsure');
        $requirement['message'] = tra('Unable to check database requirements');

        if ($dbEngine && $dbVersion) {
            $requirement['fitness'] = tra('info');
            $requirement['message'] = tra('Supported version');

            if (count($compatibleVersions) == 0) {
                $requirement['fitness'] = tra('good');
                $requirement['message'] = tra('Recommended version');
            }
        }

        $compatibleVersions[] = $requirement;
    }
    return $compatibleVersions;
}

function checkTikiVersionCompatible($compatibleVersions, $majorVersion)
{
    foreach ($compatibleVersions as $tiki) {
        if ($tiki['version'] == $majorVersion) {
            return true;
        }
    }
    return false;
}

if (file_exists('./db/local.php') && file_exists('./templates/tiki-check.tpl')) {
    $standalone = false;
    require_once('tiki-setup.php');
    // TODO : Proper authentication
    $access->check_permission('tiki_p_admin');

    // This page is an admin tool usually used in the early stages of setting up Tiki, before layout considerations.
    // Restricting the width is contrary to its purpose.
    $prefs['feature_fixed_width'] = 'n';
} else {
    $standalone = true;
    $render = "";

    /**
     * @param $string
     * @return mixed
     */
    function tra($string)
    {
        return $string;
    }

    function tr($string)
    {
        return tra($string);
    }

    /**
      * @param $var
      * @param $style
      */
    function renderTable($var, $style = "")
    {
        global $render;
        $morestyle = "";
        if ($style == "wrap") {
            $morestyle = "overflow-wrap: anywhere;";
        }
        if (is_array($var)) {
            $render .= '<table class="table table-bordered" style="' . $morestyle . '">';
            $render .= "<thead><tr></tr></thead>";
            $render .= "<tbody>";
            foreach ($var as $key => $value) {
                $render .= "<tr>";
                $render .= '<th><span class="visible-on-mobile">Property:&nbsp;</span>';
                $render .= $key;
                $render .= "</th>";
                $iNbCol = 0;
                foreach ($var[$key] as $key2 => $value2) {
                    $render .= '<td data-th="' . $key2 . ':&nbsp;" style="';
                    if ($iNbCol != count(array_keys($var[$key])) - 1) {
                        $render .= 'text-align: center;white-space:nowrap;';
                    }
                    $render .= '"><span class="';
                    switch ($value2) {
                        case 'good':
                        case 'safe':
                        case 'unsure':
                        case 'bad':
                        case 'risky':
                        case 'info':
                            $render .= "button $value2";
                            break;
                    }
                    $render .= '">' . $value2 . '</span></td>';
                    $iNbCol++;
                }
                $render .= '</tr>';
            }
            $render .= '</tbody></table>';
        } else {
            $render .= 'Nothing to display.';
        }
    }

    /**
     * @param $var
     */
    function renderAvailableTikiTable($var)
    {
        global $render;

        $formatValue = function ($value, $property) {
            return $value[$property]['min'] .
                ((! empty($value[$property]['max']) && $value[$property]['max'] != $value[$property]['min'])
                    ? ' - ' . $value[$property]['max']
                    : (empty($value[$property]['max']) ? '+' : ''));
        };
        if (is_array($var) && ! empty($var)) {
            $render .= '<table class="table table-bordered"><thead>';
            $render .= '<tr><th>Version</th><th>PHP</th><th>MySQL</th><th>MariaDB</th>';
            $render .= '<th>Fitness</th><th>Explanation</th></tr>';
            foreach ($var as $value) {
                $phpReq = $formatValue($value, 'php');
                $mysqlReq = $formatValue($value, 'mysql');
                $mariadbReq = $formatValue($value, 'mariadb');
                $render .= '<th> ' . $value['name'] . ' </th>';
                $render .= '<td> ' . $phpReq . ' </td>';
                $render .= '<td> ' . $mysqlReq . ' </td>';
                $render .= '<td> ' . $mariadbReq . ' </td>';
                $render .= '<td><span class="button ' . $value['fitness'] . '">' . $value['fitness'] . '</span> </td>';
                $render .= '<td> ' . $value['message'] . ' </td></tr>';
            }
            $render .= '</tbody></table>';
        } else {
            $render .= 'Nothing to display.';
        }

        $render .= '<p>For more details, check the <a href="https://doc.tiki.org/Requirements" target="_blank">Tiki Requirements</a> documentation.</p>';
    }
}

// Get PHP properties and check them
$php_properties = array();

if (! file_exists('./db/local.php')) {
    $errorMessage = tra('Mysql benchmark was skipped because no DB configuration was found.');

    if ($standalone) {
        $errorMessage = tra('Mysql benchmark was skipped because it was running as standalone and no DB configuration was found.');
    }

    $render .= '<div class="alert alert-danger"><div class="rboxcontent" style="display: inline">' . $errorMessage . '</div></div>';
}

// Check error reporting level
$e = error_reporting();
$d = ini_get('display_errors');
$l = ini_get('log_errors');
if ($l) {
    if (! $d) {
        $php_properties['Error logging'] = array(
        'fitness' => tra('info'),
        'setting' => 'Enabled',
        'message' => tra('Errors will be logged, since log_errors is enabled. Also, display_errors is disabled. This is good practice for a production site, to log the errors instead of displaying them.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
        );
    } else {
        $php_properties['Error logging'] = array(
        'fitness' => tra('info'),
        'setting' => 'Enabled',
        'message' => tra('Errors will be logged, since log_errors is enabled, but display_errors is also enabled. Good practice, especially for a production site, is to log all errors instead of displaying them.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
        );
    }
} else {
    $php_properties['Error logging'] = array(
    'fitness' => tra('info'),
    'setting' => 'Full',
    'message' => tra('Errors will not be logged, since log_errors is not enabled. Good practice, especially for a production site, is to log all errors.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
}
if ($e == 0) {
    if ($d != 1) {
        $php_properties['Error reporting'] = array(
            'fitness' => tra('info'),
            'setting' => 'Disabled',
            'message' => tra('Errors will not be reported, because error_reporting and display_errors are both turned off. This may be appropriate for a production site but, if any problems occur, enable these in php.ini to get more information.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
        );
    } else {
        $php_properties['Error reporting'] = array(
            'fitness' => tra('info'),
            'setting' => 'Disabled',
            'message' => tra('No errors will be reported, although display_errors is On, because the error_reporting level is set to 0. This may be appropriate for a production site but, in if any problems occur, raise the value in php.ini to get more information.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
        );
    }
} elseif ($e > 0 && $e < 32767) {
    if ($d != 1) {
        $php_properties['Error reporting'] = array(
            'fitness' => tra('info'),
            'setting' => 'Disabled',
            'message' => tra('No errors will be reported, because display_errors is turned off. This may be appropriate for a production site but, in any problems occur, enable it in php.ini to get more information. The error_reporting level is reasonable at ' . $e . '.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
        );
    } else {
        $php_properties['Error reporting'] = array(
            'fitness' => tra('info'),
            'setting' => 'Partly',
            'message' => tr('Not all errors will be reported as the error_reporting level is at %0. This is not necessarily a bad thing (and it may be appropriate for a production site) as critical errors will be reported, but sometimes it may be useful to get more information. Check the error_reporting level in php.ini if any problems are occurring. <a href="#php_conf_info">How to change this value</a>', $e)
        );
    }
} else {
    if ($d != 1) {
        $php_properties['Error reporting'] = array(
            'fitness' => tra('info'),
            'setting' => 'Disabled',
            'message' => tra('No errors will be reported although the error_reporting level is all the way up at ' . $e . ', because display_errors is off. This may be appropriate for a production site but, in case of problems, enable it in php.ini to get more information.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
        );
    } else {
        $php_properties['Error reporting'] = array(
            'fitness' => tra('info'),
            'setting' => 'Full',
            'message' => tra('All errors will be reported as the error_reporting level is all the way up at ' . $e . ' and display_errors is on. This is good because, in case of problems, the error reports usually contain useful information.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
        );
    }
}

// Now we can raise our error_reporting to make sure we get all errors
// This is especially important as we can't use proper exception handling with PDO as we need to be PHP 4 compatible
error_reporting(-1);

// Check if ini_set works
if (function_exists('ini_set')) {
    $php_properties['ini_set'] = array(
        'fitness' => tra('good'),
        'setting' => 'Enabled',
        'message' => tra('ini_set is used in some places to accommodate special needs of some Tiki features.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
    // As ini_set is available, use it for PDO error reporting
    ini_set('display_errors', '1');
} else {
    $php_properties['ini_set'] = array(
        'fitness' => tra('unsure'),
        'setting' => 'Disabled',
        'message' => tra('ini_set is used in some places to accommodate special needs of some Tiki features. Check disable_functions in your php.ini.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
}

// First things first
// If we don't have a DB-connection, some tests don't run
$s = extension_loaded('pdo_mysql');
if ($s) {
    $php_properties['DB Driver'] = array(
        'fitness' => tra('good'),
        'setting' => 'PDO',
        'message' => tra('The PDO extension is the suggested database driver/abstraction layer.')
    );
} elseif ($s = extension_loaded('mysqli')) {
    $php_properties['DB Driver'] = array(
        'fitness' => tra('unsure'),
        'setting' => 'MySQLi',
        'message' => tra('The recommended PDO database driver/abstraction layer cannot be found. The MySQLi driver is available, though, so the database connection will fall back to the AdoDB abstraction layer that is bundled with Tiki.')
    );
} elseif (extension_loaded('mysql')) {
    $php_properties['DB Driver'] = array(
        'fitness' => tra('unsure'),
        'setting' => 'MySQL',
        'message' => tra('The recommended PDO database driver/abstraction layer cannot be found. The MySQL driver is available, though, so the database connection will fall back to the AdoDB abstraction layer that is bundled with Tiki.')
    );
} else {
    $php_properties['DB Driver'] = array(
        'fitness' => tra('bad'),
        'setting' => 'Not available',
        'message' => tra('None of the supported database drivers (PDO/mysqli/mysql) is loaded. This prevents Tiki from functioning.')
    );
}

// Now connect to the DB and make all our connectivity methods work the same
$connection = false;
if ($standalone && ! $locked) {
    if (empty($_POST['dbhost']) && ! ($php_properties['DB Driver']['setting'] == 'Not available')) {
            $render .= <<<DBC
<h2>Database credentials</h2>
Couldn't connect to database, please provide valid credentials.
<form method="post" action="{$_SERVER['SCRIPT_NAME']}">
    <div class="tiki-form-group mt-3">
        <label for="dbhost">Database host</label>
        <input class="form-control" type="text" id="dbhost" name="dbhost" value="localhost" />
    </div>
    <div class="tiki-form-group">
        <label for="dbuser">Database username</label>
        <input class="form-control" type="text" id="dbuser" name="dbuser" />
    </div>
    <div class="tiki-form-group">
        <label for="dbpass">Database password</label>
        <input class="form-control" type="password" id="dbpass" name="dbpass" />
    </div>
    <div class="tiki-form-group">
        <input type="submit" class="btn btn-primary btn-sm" value=" Connect " />
    </div>
</form>
DBC;
    } else {
        try {
            switch ($php_properties['DB Driver']['setting']) {
                case 'PDO':
                    // We don't do exception handling here to be PHP 4 compatible
                    $connection = new PDO('mysql:host=' . $_POST['dbhost'], $_POST['dbuser'], $_POST['dbpass']);
                    /**
                      * @param $query
                       * @param $connection
                       * @return mixed
                      */
                    function query($query, $connection)
                    {
                        $result = $connection->query($query);
                        $return = $result->fetchAll();
                        return($return);
                    }
                    break;
                case 'MySQLi':
                    $error = false;
                    $connection = new mysqli($_POST['dbhost'], $_POST['dbuser'], $_POST['dbpass']);
                    $error = mysqli_connect_error();
                    if (! empty($error)) {
                        $connection = false;
                        $render .= 'Couldn\'t connect to database: ' . htmlspecialchars($error);
                    }
                    /**
                     * @param $query
                     * @param $connection
                     * @return array
                     */
                    function query($query, $connection)
                    {
                        $result = $connection->query($query);
                        $return = array();
                        while ($row = $result->fetch_assoc()) {
                            $return[] = $row;
                        }
                        return($return);
                    }
                    break;
                default:
                    throw new Exception('Unsupported database driver.');
            }
        } catch (Exception $e) {
            $render .= 'Cannot connect to MySQL. Error: ' . htmlspecialchars($e->getMessage());
        }
    }
} else {
    /**
      * @param $query
      * @return array
      */
    function query($query)
    {
        global $tikilib;
        $result = $tikilib->query($query);
        $return = array();
        while ($row = $result->fetchRow()) {
            $return[] = $row;
        }
        return($return);
    }
}

// Basic Server environment
$server_information['Operating System'] = array(
    'value' => PHP_OS,
);

if (PHP_OS == 'Linux' && function_exists('exec')) {
    exec('lsb_release -d', $output, $retval);
    if ($retval == 0) {
        $server_information['Release'] = array(
            'value' => str_replace('Description:', '', $output[0])
        );
        # Check for FreeType fails without a font, i.e. standalone mode
        # Using a URL as font source doesn't work on all PHP installs
        # So let's try to gracefully fall back to some locally installed font at least on Linux
        if (! file_exists($font)) {
            $font = exec('find /usr/share/fonts/ -type f -name "*.ttf" | head -n 1', $output);
        }
    } else {
        $server_information['Release'] = array(
            'value' => tra('N/A')
        );
    }
}

$server_information['Web Server'] = array(
    'value' => $_SERVER['SERVER_SOFTWARE']
);

$server_information['Server Signature']['value'] = ! empty($_SERVER['SERVER_SIGNATURE']) ? $_SERVER['SERVER_SIGNATURE'] : 'off';

// Free disk space
if (function_exists('disk_free_space')) {
    $bytes = @disk_free_space('.');    // this can fail on 32 bit systems with lots of disc space so suppress the possible warning
    $si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
    $base = 1024;
    $class = min((int) log($bytes, $base), count($si_prefix) - 1);
    $free_space = sprintf('%1.2f', $bytes / pow($base, $class)) . ' ' . $si_prefix[$class];
    if ($bytes === false) {
        $server_properties['Disk Space'] = array(
            'fitness' => 'unsure',
            'setting' => tra('Unable to detect'),
            'message' => tra('Cannot determine the size of this disk drive.')
        );
    } elseif ($bytes < 200 * 1024 * 1024) {
        $server_properties['Disk Space'] = array(
            'fitness' => 'bad',
            'setting' => $free_space,
            'message' => tra('Less than 200MB of free disk space is available. Tiki will not fit in this amount of disk space.')
        );
    } elseif ($bytes < 250 * 1024 * 1024) {
        $server_properties['Disk Space'] = array(
            'fitness' => 'unsure',
            'setting' => $free_space,
            'message' => tra('Less than 250MB of free disk space is available. This would be quite tight for a Tiki installation. Tiki needs disk space for compiled templates and uploaded files.') . ' ' . tra('When the disk space is filled, users, including administrators, will not be able to log in to Tiki.') . ' ' . tra('This test cannot reliably check for quotas, so be warned that if this server makes use of them, there might be less disk space available than reported.')
        );
    } else {
        $server_properties['Disk Space'] = array(
            'fitness' => 'good',
            'setting' => $free_space,
            'message' => tra('More than 251MB of free disk space is available. Tiki will run smoothly, but there may be issues when the site grows (because of file uploads, for example).') . ' ' . tra('When the disk space is filled, users, including administrators, will not be able to log in to Tiki.') . ' ' . tra('This test cannot reliably check for quotas, so be warned that if this server makes use of them, there might be less disk space available than reported.')
        );
    }
} else {
        $server_properties['Disk Space'] = array(
            'fitness' => 'N/A',
            'setting' => 'N/A',
            'message' => tra('The PHP function disk_free_space is not available on your server, so the amount of available disk space can\'t be checked for.')
        );
}

if (! $standalone) {
    $tikiWikiVersion = new TWVersion();
    $tikiBaseVersion = $tikiWikiVersion->getBaseVersion();
}

/**
 * @param string $tikiBaseVersion
 * @param string $min The first minimum value in bounds, for example 15.0 if support 15.x or newer
 * @param string $max The first value out of bounds, for example 16.0 if only support up to 15.x
 *
 * @return bool
 */
function isVersionInRange($version, $min, $max)
{
    return version_compare($version, $min, '>=')
        && version_compare($version, $max, '<');
}
$tikiRequirements = getTikiRequirements();

$minCompatibleTikiVersion = null;
$maxCompatibleTikiVersion = null;
foreach ($tikiRequirements as $requirement) {
    if (isVersionInRange(PHP_VERSION, $requirement['php']['min'], $requirement['php']['max'])) {
        //Remember the list is sorted from most recent to oldest
        $minCompatibleTikiVersion = $requirement['version'] ?: $minCompatibleTikiVersion;
        $maxCompatibleTikiVersion = $maxCompatibleTikiVersion ?: $requirement['version'];
    }
}
$php_properties['PHP version'] = array(
    'fitness' => ($minCompatibleTikiVersion && $maxCompatibleTikiVersion) ? tra('good') : tra('bad'),
    'setting' => PHP_VERSION,
    'message' => tr("Tiki %0.x to Tiki %1.x will work fine on this version of PHP. Please see http://doc.tiki.org/Requirements for details.", $minCompatibleTikiVersion, $maxCompatibleTikiVersion)
);

// Check PHP command line version
if (function_exists('exec')) {
    $cliSearchList = array('php', 'php56', 'php5.6', 'php5.6-cli');
    $isUnix = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? false : true;
    if ($isUnix) {
        // add virtualmin per-domain php configurations
        array_unshift($cliSearchList, __DIR__ . '/bin/php');
        array_unshift($cliSearchList, __DIR__ . '/../bin/php');
    }
    $cliCommand = '';
    $cliVersion = '';
    foreach ($cliSearchList as $command) {
        if ($isUnix) {
            $output = exec('command -v ' . escapeshellarg($command) . ' 2>/dev/null');
        } else {
            $output = exec('where ' . escapeshellarg($command . '.exe'));
        }
        if (! $output) {
            continue;
        }

        $cliCommand = trim($output);
        exec(escapeshellcmd(trim($cliCommand)) . ' --version', $output);
        foreach ($output as $line) {
            $parts = explode(' ', $line);
            if ($parts[0] === 'PHP') {
                $cliVersion = $parts[1];
                break;
            }
        }
        break;
    }
    if ($cliCommand) {
        if (PHP_VERSION == $cliVersion) {
            $php_properties['PHP CLI version'] = array(
                'fitness' => tra('good'),
                'setting' => $cliVersion,
                'message' => 'The version of the command line executable of PHP (' . $cliCommand . ') is the same version as the web server version.',
            );
        } else {
            $php_properties['PHP CLI version'] = array(
                'fitness' => tra('unsure'),
                'setting' => $cliVersion,
                'message' => 'The version of the command line executable of PHP (' . $cliCommand . ') is not the same as the web server version.',
            );
        }
    } else {
        $php_properties['PHP CLI version'] = array(
            'fitness' => tra('unsure'),
            'setting' => '',
            'message' => tra('Unable to determine the command line executable for PHP.'),
        );
    }
}

// PHP Server API (SAPI)
if (substr(PHP_SAPI, 0, 3) === 'cgi') {
    $php_properties['PHP Server API'] = array(
        'fitness' => tra('info'),
        'setting' => PHP_SAPI,
        'message' => tra('PHP is being run as CGI. Feel free to use a threaded Apache MPM to increase performance.')
    );

    $php_sapi_info = array(
        'message' => tra('Looks like you are running PHP as FPM/CGI/FastCGI, you may be able to override some of your PHP configurations by add them to .user.ini files, see:'),
        'link' => 'http://php.net/manual/en/configuration.file.per-user.php'
    );
} elseif (substr(PHP_SAPI, 0, 3) === 'fpm') {
    $php_properties['PHP Server API'] = array(
        'fitness' => tra('info'),
        'setting' => PHP_SAPI,
        'message' => tra('PHP is being run using FPM (Fastcgi Process Manager). Feel free to use a threaded Apache MPM to increase performance.')
    );

    $php_sapi_info = array(
        'message' => tra('Looks like you are running PHP as FPM/CGI/FastCGI, you may be able to override some of your PHP configurations by add them to .user.ini files, see:'),
        'link' => 'http://php.net/manual/en/configuration.file.per-user.php'
    );
} else {
    if (substr(PHP_SAPI, 0, 6) === 'apache') {
        $php_sapi_info = array(
            'message' => tra('Looks like you are running PHP as a module in Apache, you may be able to override some of your PHP configurations by add them to .htaccess files, see:'),
            'link' => 'http://php.net/manual/en/configuration.changes.php#configuration.changes.apache'
        );
    }

    $php_properties['PHP Server API'] = array(
        'fitness' => tra('info'),
        'setting' => PHP_SAPI,
        'message' => tra('PHP is not being run as CGI. Be aware that PHP is not thread-safe and you should not use a threaded Apache MPM (like worker).')
    );
}

// ByteCode Cache
if (function_exists('opcache_get_configuration') && (ini_get('opcache.enable') == 1 || ini_get('opcache.enable') == '1')) {
    $message = tra('OPcache is being used as the ByteCode Cache, which increases performance if correctly configured. See Admin->Performance in the Tiki for more details.');
    $fitness = tra('good');
    if (! checkOPcacheCompatibility()) {
        $message = tra('Some PHP versions may exhibit randomly issues with the OPcache leading to the server starting to fail to serve all PHP requests, your PHP version seems to
         be affected, despite the performance penalty, we would recommend disabling the OPcache if you experience random crashes.');
        $fitness = tra('unsure');
    }
    $php_properties['ByteCode Cache'] = array(
        'fitness' => $fitness,
        'setting' => 'OPcache',
        'message' => $message
    );
} elseif (function_exists('wincache_fcache_fileinfo')) {
    // Determine if version 1 or 2 is used. Version 2 does not support ocache

    if (function_exists('wincache_ocache_fileinfo')) {
        // Wincache version 1
        if (ini_get('wincache.ocenabled') == '1') {
            if (PHP_SAPI == 'cgi-fcgi') {
                $php_properties['ByteCode Cache'] = array(
                    'fitness' => tra('good'),
                    'setting' => 'WinCache',
                    'message' => tra('WinCache is being used as the ByteCode Cache, which increases performance if correctly configured. See Admin->Performance in the Tiki for more details.')
                );
            } else {
                $php_properties['ByteCode Cache'] = array(
                    'fitness' => tra('unsure'),
                    'setting' => 'WinCache',
                    'message' => tra('WinCache is being used as the ByteCode Cache, but the required CGI/FastCGI server API is apparently not being used.')
                );
            }
        } else {
            no_cache_found();
        }
    } else {
        // Wincache version 2 or higher
        if (ini_get('wincache.fcenabled') == '1') {
            if (PHP_SAPI == 'cgi-fcgi') {
                $php_properties['ByteCode Cache'] = array(
                    'fitness' => tra('info'),
                    'setting' => 'WinCache',
                    'message' => tra('WinCache version 2 or higher is being used as the FileCache. It does not support a ByteCode Cache.') . ' ' . tra('It is recommended to use Zend opcode cache as the ByteCode Cache.')
                );
            } else {
                $php_properties['ByteCode Cache'] = array(
                    'fitness' => tra('unsure'),
                    'setting' => 'WinCache',
                    'message' => tra('WinCache version 2 or higher is being used as the FileCache, but the required CGI/FastCGI server API is apparently not being used.') . ' ' . tra('It is recommended to use Zend opcode cache as the ByteCode Cache.')
                );
            }
        } else {
            no_cache_found();
        }
    }
} else {
    no_cache_found();
}


// memory_limit
$memory_limit = ini_get('memory_limit');
$s = trim($memory_limit);
$last = strtolower(substr($s, -1));
if (! is_numeric($last)) {
    $s = substr($s, 0, -1);
}
switch ($last) {
    case 'g':
        $s *= 1024;
        // no break
    case 'm':
        $s *= 1024;
        // no break
    case 'k':
        $s *= 1024;
}
if ($s >= 160 * 1024 * 1024) {
    $php_properties['memory_limit'] = array(
        'fitness' => tra('good'),
        'setting' => $memory_limit,
        'message' => tra('The memory_limit is at') . ' ' . $memory_limit . '. ' . tra('This is known to support smooth functioning even for bigger sites.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} elseif ($s < 160 * 1024 * 1024 && $s > 127 * 1024 * 1024) {
    $php_properties['memory_limit'] = array(
        'fitness' => tra('unsure') ,
        'setting' => $memory_limit,
        'message' => tra('The memory_limit is at') . ' ' . $memory_limit . '. ' . tra('This will normally work, but the site might run into problems when it grows.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} elseif ($s == -1) {
    $php_properties['memory_limit'] = array(
        'fitness' => tra('unsure') ,
        'setting' => $memory_limit,
        'message' => tra("The memory_limit is unlimited. This is not necessarily bad, but it's a good idea to limit this on productions servers in order to eliminate unexpectedly greedy scripts.") . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} else {
    $php_properties['memory_limit'] = array(
        'fitness' => tra('bad'),
        'setting' => $memory_limit,
        'message' => tra('Your memory_limit is at') . ' ' . $memory_limit . '. ' . tra('This is known to cause issues! The memory_limit should be increased to at least 128M, which is the PHP default.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
}

// session.save_handler
$s = ini_get('session.save_handler');
if ($s != 'files') {
    $php_properties['session.save_handler'] = array(
        'fitness' => tra('unsure'),
        'setting' => $s,
        'message' => tra('The session.save_handler should be set to \'files\'.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} else {
    $php_properties['session.save_handler'] = array(
        'fitness' => tra('good'),
        'setting' => $s,
        'message' => tra('Well set! The default setting of \'files\' is recommended for Tiki.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
}

// session.save_path
$s = ini_get('session.save_path');
if ($php_properties['session.save_handler']['setting'] == 'files') {
    if (empty($s) || ! is_writable($s)) {
        $php_properties['session.save_path'] = array(
            'fitness' => tra('bad'),
            'setting' => $s,
            'message' => tra('The session.save_path must be writable.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
        );
    } else {
        $php_properties['session.save_path'] = array(
            'fitness' => tra('good'),
            'setting' => $s,
            'message' => tra('The session.save_path is writable.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
        );
    }
} else {
    if (empty($s) || ! is_writable($s)) {
        $php_properties['session.save_path'] = array(
            'fitness' => tra('unsure'),
            'setting' => $s,
            'message' => tra('If you would be using the recommended session.save_handler setting of \'files\', the session.save_path would have to be writable. Currently it is not.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
        );
    } else {
        $php_properties['session.save_path'] = array(
            'fitness' => tra('info'),
            'setting' => $s,
            'message' => tra('The session.save_path is writable.') . tra('It doesn\'t matter though, since your session.save_handler is not set to \'files\'.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
        );
    }
}

$s = ini_get('session.gc_probability');
$php_properties['session.gc_probability'] = array(
    'fitness' => tra('info'),
    'setting' => $s,
    'message' => tra('In conjunction with gc_divisor is used to manage probability that the gc (garbage collection) routine is started.')
);

$s = ini_get('session.gc_divisor');
$php_properties['session.gc_divisor'] = array(
    'fitness' => tra('info'),
    'setting' => $s,
    'message' => tra('Coupled with session.gc_probability defines the probability that the gc (garbage collection) process is started on every session initialization. The probability is calculated by using gc_probability/gc_divisor, e.g. 1/100 means there is a 1% chance that the GC process starts on each request.')
);

$s = ini_get('session.gc_maxlifetime');
$php_properties['session.gc_maxlifetime'] = array(
    'fitness' => tra('info'),
    'setting' => $s . 's',
    'message' => tra('Specifies the number of seconds after which data will be seen as \'garbage\' and potentially cleaned up. Garbage collection may occur during session start.')
);

// test session work
@session_start();

if (empty($_SESSION['tiki-check'])) {
    $php_properties['session'] = array(
        'fitness' => tra('unsure'),
        'setting' => tra('empty'),
        'message' => tra('The session is empty. Try reloading the page and, if this message is displayed again, there may be a problem with the server setup.')
    );
    $_SESSION['tiki-check'] = 1;
} else {
    $php_properties['session'] = array(
        'fitness' => tra('good'),
        'setting' => 'ok',
        'message' => tra('This appears to work.')
    );
}

// zlib.output_compression
$s = ini_get('zlib.output_compression');
if ($s) {
    $php_properties['zlib.output_compression'] = array(
        'fitness' => tra('info'),
        'setting' => 'On',
        'message' => tra('zlib output compression is turned on. This saves bandwidth. On the other hand, turning it off would reduce CPU usage. The appropriate choice can be made for this Tiki.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} else {
    $php_properties['zlib.output_compression'] = array(
        'fitness' => tra('info'),
        'setting' => 'Off',
        'message' => tra('zlib output compression is turned off. This reduces CPU usage. On the other hand, turning it on would save bandwidth. The appropriate choice can be made for this Tiki.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
}

// default_charset
$s = ini_get('default_charset');
if (strtolower($s) == 'utf-8') {
    $php_properties['default_charset'] = array(
        'fitness' => tra('good'),
        'setting' => $s,
        'message' => tra('Correctly set! Tiki is fully UTF-8 and so should be this installation.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} else {
    $php_properties['default_charset'] = array(
        'fitness' => tra('unsure'),
        'setting' => $s,
        'message' => tra('default_charset should be UTF-8 as Tiki is fully UTF-8. Please check the php.ini file.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
}

// date.timezone
$s = ini_get('date.timezone');
if (empty($s)) {
    $php_properties['date.timezone'] = array(
        'fitness' => tra('unsure'),
        'setting' => $s,
        'message' => tra('No time zone is set! While there are a number of fallbacks in PHP to determine the time zone, the only reliable solution is to set it explicitly in php.ini! Please check the value of date.timezone in php.ini.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} else {
    $php_properties['date.timezone'] = array(
        'fitness' => tra('good'),
        'setting' => $s,
        'message' => tra('Well done! Having a time zone set protects the site from related errors.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
}

$tempDir = sys_get_temp_dir();
$tmpfile = tempnam($tempDir, 'symfony');

if (! is_writable($tmpfile) || empty($tmpfile)) {
    $php_properties['sys_get_temp_dir'] = array(
        'fitness' => tra('bad'),
        'setting' => '',
        'message' => tra("Temporary folder is set to $tempDir, but it is not accessible by Tiki.")
    );
} else {
    $php_properties['sys_get_temp_dir'] = array(
        'fitness' => tra('good'),
        'setting' => 'Ok',
        'message' => tra('The Temporary is accessible and writable by Tiki.')
    );
}

// file_uploads
$s = ini_get('file_uploads');
if ($s) {
    $php_properties['file_uploads'] = array(
        'fitness' => tra('good'),
        'setting' => 'On',
        'message' => tra('Files can be uploaded to Tiki.')
    );
} else {
    $php_properties['file_uploads'] = array(
        'fitness' => tra('bad'),
        'setting' => 'Off',
        'message' => tra('Files cannot be uploaded to Tiki.')
    );
}

// max_execution_time
$s = ini_get('max_execution_time');
if ($s >= 30 && $s <= 90) {
    $php_properties['max_execution_time'] = array(
        'fitness' => tra('good'),
        'setting' => $s . 's',
        'message' => tra('The max_execution_time is at') . ' ' . $s . '. ' . tra('This is a good value for production sites. If timeouts are experienced (such as when performing admin functions) this may need to be increased nevertheless.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} elseif ($s == -1 || $s == 0) {
    $php_properties['max_execution_time'] = array(
        'fitness' => tra('unsure'),
        'setting' => $s . 's',
        'message' => tra('The max_execution_time is unlimited.') . ' ' . tra('This is not necessarily bad, but it\'s a good idea to limit this time on productions servers in order to eliminate unexpectedly long running scripts.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} elseif ($s > 90) {
    $php_properties['max_execution_time'] = array(
        'fitness' => tra('unsure'),
        'setting' => $s . 's',
        'message' => tra('The max_execution_time is at') . ' ' . $s . '. ' . tra('This is not necessarily bad, but it\'s a good idea to limit this time on productions servers in order to eliminate unexpectedly long running scripts.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} else {
    $php_properties['max_execution_time'] = array(
        'fitness' => tra('bad'),
        'setting' => $s . 's',
        'message' => tra('The max_execution_time is at') . ' ' . $s . '. ' . tra('It is likely that some scripts, such as admin functions, will not finish in this time! The max_execution_time should be incresed to at least 30s.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
}

// max_input_time
$s = ini_get('max_input_time');
if ($s >= 30 && $s <= 90) {
    $php_properties['max_input_time'] = array(
        'fitness' => tra('good'),
        'setting' => $s . 's',
        'message' => tra('The max_input_time is at') . ' ' . $s . '. ' . tra('This is a good value for production sites. If timeouts are experienced (such as when performing admin functions) this may need to be increased nevertheless.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} elseif ($s == -1 || $s == 0) {
    $php_properties['max_input_time'] = array(
        'fitness' => tra('unsure'),
        'setting' => $s . 's',
        'message' => tra('The max_input_time is unlimited.') . ' ' . tra('This is not necessarily bad, but it\'s a good idea to limit this time on productions servers in order to eliminate unexpectedly long running scripts.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} elseif ($s > 90) {
    $php_properties['max_input_time'] = array(
        'fitness' => tra('unsure'),
        'setting' => $s . 's',
        'message' => tra('The max_input_time is at') . ' ' . $s . '. ' . tra('This is not necessarily bad, but it\'s a good idea to limit this time on productions servers in order to eliminate unexpectedly long running scripts.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} else {
    $php_properties['max_input_time'] = array(
        'fitness' => tra('bad'),
        'setting' => $s . 's',
        'message' => tra('The max_input_time is at') . ' ' . $s . '. ' . tra('It is likely that some scripts, such as admin functions, will not finish in this time! The max_input_time should be increased to at least 30 seconds.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
}
// max_file_uploads
$max_file_uploads = ini_get('max_file_uploads');
if ($max_file_uploads) {
    $php_properties['max_file_uploads'] = array(
        'fitness' => tra('info'),
        'setting' => $max_file_uploads,
        'message' => tra('The max_file_uploads is at') . ' ' . $max_file_uploads . '. ' . tra('This is the maximum number of files allowed to be uploaded simultaneously.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} else {
    $php_properties['max_file_uploads'] = array(
        'fitness' => tra('info'),
        'setting' => 'Not Available',
        'message' => tra('The maximum number of files allowed to be uploaded is not available')
    );
}
// upload_max_filesize
$upload_max_filesize = ini_get('upload_max_filesize');
$s = trim($upload_max_filesize);
$last = strtolower(substr($s, -1));
$s = substr($s, 0, -1);
switch ($last) {
    case 'g':
        $s *= 1024;
        // no break
    case 'm':
        $s *= 1024;
        // no break
    case 'k':
        $s *= 1024;
}
if ($s >= 8 * 1024 * 1024) {
    $php_properties['upload_max_filesize'] = array(
        'fitness' => tra('good'),
        'setting' => $upload_max_filesize,
        'message' => tra('The upload_max_filesize is at') . ' ' . $upload_max_filesize . '. ' . tra('Quite large files can be uploaded, but keep in mind to set the script timeouts accordingly.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} elseif ($s == 0) {
    $php_properties['upload_max_filesize'] = array(
        'fitness' => tra('unsure'),
        'setting' => $upload_max_filesize,
        'message' => tra('The upload_max_filesize is at') . ' ' . $upload_max_filesize . '. ' . tra('Upload size is unlimited and this not advised. A user could mistakenly upload a very large file which could fill up the disk. This value should be set to accommodate the realistic needs of the site.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} else {
    $php_properties['upload_max_filesize'] = array(
        'fitness' => tra('unsure'),
        'setting' => $upload_max_filesize,
        'message' => tra('The upload_max_filesize is at') . ' ' . $upload_max_filesize . '. ' . tra('This is not a bad amount, but be sure the level is high enough to accommodate the needs of the site.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
}

// post_max_size
$post_max_size = ini_get('post_max_size');
$s = trim($post_max_size);
$last = strtolower(substr($s, -1));
$s = substr($s, 0, -1);
switch ($last) {
    case 'g':
        $s *= 1024;
        // no break
    case 'm':
        $s *= 1024;
        // no break
    case 'k':
        $s *= 1024;
}
if ($s >= 8 * 1024 * 1024) {
    $php_properties['post_max_size'] = array(
        'fitness' => tra('good'),
        'setting' => $post_max_size,
        'message' => tra('The post_max_size is at') . ' ' . $post_max_size . '. ' . tra('Quite large files can be uploaded, but keep in mind to set the script timeouts accordingly.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} else {
    $php_properties['post_max_size'] = array(
        'fitness' => tra('unsure'),
        'setting' => $post_max_size,
        'message' => tra('The post_max_size is at') . ' ' . $post_max_size . '. ' . tra('This is not a bad amount, but be sure the level is high enough to accommodate the needs of the site.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
}

// PHP Extensions
// fileinfo
$s = extension_loaded('fileinfo');
if ($s) {
    $php_properties['fileinfo'] = array(
        'fitness' => tra('good'),
        'setting' => 'Loaded',
        'message' => tra("The fileinfo extension is needed for the 'Validate uploaded file content' preference.")
    );
} else {
    $php_properties['fileinfo'] = array(
        'fitness' => tra('unsure'),
        'setting' => 'Not available',
        'message' => tra("The fileinfo extension is needed for the 'Validate uploaded file content' preference.")
    );
}

// intl
$s = extension_loaded('intl');
if ($s) {
    $php_properties['intl'] = array(
        'fitness' => tra('good'),
        'setting' => 'Loaded',
        'message' => tra("The intl extension is required for Tiki 15 and newer.")
    );
} else {
    $php_properties['intl'] = array(
        'fitness' => tra('unsure'),
        'setting' => 'Not available',
        'message' => tra("The intl extension is preferred for Tiki 15 and newer. While a polyfill is used to emulate some of the features, for better performance and broader language support. It’s recommended that you install the intl extension for PHP, more information on https://www.php.net/intl.")
    );
}

// GD
$s = extension_loaded('gd');
if ($s && function_exists('gd_info')) {
    $gd_info = gd_info();
    $im = $ft = null;
    if (function_exists('imagecreate')) {
        $im = @imagecreate(110, 20);
    }
    if (function_exists('imageftbbox')) {
        $ft = @imageftbbox(12, 0, $font, 'test');
    }
    if ($im && $ft) {
        $php_properties['gd'] = array(
            'fitness' => tra('good'),
            'setting' => $gd_info['GD Version'],
            'message' => tra('The GD extension is needed for manipulation of images and for CAPTCHA images.')
        );
        imagedestroy($im);
    } elseif ($im) {
        $php_properties['gd'] = array(
                'fitness' => tra('unsure'),
                'setting' => $gd_info['GD Version'],
                'message' => tra('The GD extension is loaded, and Tiki can create images, but the FreeType extension is needed for CAPTCHA text generation.')
            );
            imagedestroy($im);
    } else {
        $php_properties['gd'] = array(
            'fitness' => tra('unsure'),
            'setting' => 'Dysfunctional',
            'message' => tra('The GD extension is loaded, but Tiki is unable to create images. Please check your GD library configuration.')
        );
    }
} else {
    $php_properties['gd'] = array(
        'fitness' => tra('bad'),
        'setting' => 'Not available',
        'message' => tra('The GD extension is needed for manipulation of images and for CAPTCHA images.')
    );
}

// Image Magick
$s = class_exists('Imagick');
if ($s) {
    $image = new Imagick();
    $image->newImage(100, 100, new ImagickPixel('red'));
    if ($image) {
        $php_properties['Image Magick'] = array(
            'fitness' => tra('good'),
            'setting' => 'Available',
            'message' => tra('ImageMagick is used as a fallback in case GD is not available.')
        );
        $image->destroy();
    } else {
        $php_properties['Image Magick'] = array(
            'fitness' => tra('unsure'),
            'setting' => 'Dysfunctional',
            'message' => tra('ImageMagick is used as a fallback in case GD is not available.') . tra('ImageMagick is available, but unable to create images. Please check your ImageMagick configuration.')
            );
    }
} else {
    $php_properties['Image Magick'] = array(
        'fitness' => tra('info'),
        'setting' => 'Not Available',
        'message' => tra('ImageMagick is used as a fallback in case GD is not available.')
        );
}

// mbstring
$s = extension_loaded('mbstring');
if ($s) {
    // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.mbstring_func_overloadDeprecatedRemoved -- tiki-check supports also older versions of PHP
    $func_overload = ini_get('mbstring.func_overload');
    if (! function_exists('mb_split')) {
        $php_properties['mbstring'] = array(
            'fitness' => tra('bad'),
            'setting' => 'Badly installed',
            'message' => tra('mbstring extension is loaded, but missing important functions such as mb_split(). Reinstall it with --enable-mbregex or ask your a server administrator to do it.')
        );
    } elseif ($func_overload !== false || $func_overload > 0) {//Yes, this reads weird.  But in php 8 func_overload no longer exists.  See https://www.php.net/manual/en/mbstring.overload.php
        $php_properties['mbstring'] = array(
            'fitness' => tra('unsure'),
            'setting' => 'Badly configured',
            'message' => tra('mbstring extension is loaded, but mbstring.func_overload = ' . ' ' . $func_overload . '.' . ' ' . 'Tiki only works with mbstring.func_overload = 0. Please check the php.ini file.')
            );
    } else {
        $php_properties['mbstring'] = array(
            'fitness' => tra('good'),
            'setting' => 'Loaded',
            'message' => tra('mbstring extension is needed for an UTF-8 compatible lower case filter, in the admin search for example.')
        );
    }
} else {
    $php_properties['mbstring'] = array(
        'fitness' => tra('bad'),
        'setting' => 'Not available',
        'message' => tra('mbstring extension is needed for an UTF-8 compatible lower case filter.')
    );
}

// calendar
$s = extension_loaded('calendar');
if ($s) {
    $php_properties['calendar'] = array(
        'fitness' => tra('good'),
        'setting' => 'Loaded',
        'message' => tra('calendar extension is needed by Tiki.')
    );
} else {
    $php_properties['calendar'] = array(
        'fitness' => tra('bad'),
        'setting' => 'Not available',
        'message' => tra('calendar extension is needed by Tiki.') . ' ' . tra('The calendar feature of Tiki will not function without this.')
    );
}

// ctype
$s = extension_loaded('ctype');
if ($s) {
    $php_properties['ctype'] = array(
        'fitness' => tra('good'),
        'setting' => 'Loaded',
        'message' => tra('ctype extension is needed by Tiki.')
    );
} else {
    $php_properties['ctype'] = array(
        'fitness' => tra('bad'),
        'setting' => 'Not available',
        'message' => tra('ctype extension is needed by Tiki.')
    );
}

// libxml
$s = extension_loaded('libxml');
if ($s) {
    $php_properties['libxml'] = array(
        'fitness' => tra('good'),
        'setting' => 'Loaded',
        'message' => tra('This extension is needed for the dom extension (see below).')
    );
} else {
    $php_properties['libxml'] = array(
        'fitness' => tra('bad'),
        'setting' => 'Not available',
        'message' => tra('This extension is needed for the dom extension (see below).')
    );
}

// dom (depends on libxml)
$s = extension_loaded('dom');
if ($s) {
    $php_properties['dom'] = array(
        'fitness' => tra('good'),
        'setting' => 'Loaded',
        'message' => tra('This extension is needed by Tiki')
    );
} else {
    $php_properties['dom'] = array(
        'fitness' => tra('bad'),
        'setting' => 'Not available',
        'message' => tra('This extension is needed by Tiki')
    );
}

$s = extension_loaded('ldap');
if ($s) {
    $php_properties['LDAP'] = array(
        'fitness' => tra('good'),
        'setting' => 'Loaded',
        'message' => tra('This extension is needed to connect Tiki to an LDAP server. More info at: http://doc.tiki.org/LDAP ')
    );
} else {
    $php_properties['LDAP'] = array(
        'fitness' => tra('info'),
        'setting' => 'Not available',
        'message' => tra('Tiki will not be able to connect to an LDAP server as the needed PHP extension is missing. More info at: http://doc.tiki.org/LDAP')
    );
}

$s = extension_loaded('memcached');
if ($s) {
    $php_properties['memcached'] = array(
        'fitness' => tra('good'),
        'setting' => 'Loaded',
        'message' => tra('This extension can be used to speed up Tiki by saving sessions as well as wiki and forum data on a memcached server.')
    );
} else {
    $php_properties['memcached'] = array(
        'fitness' => tra('info'),
        'setting' => 'Not available',
        'message' => tra('This extension can be used to speed up Tiki by saving sessions as well as wiki and forum data on a memcached server.')
    );
}

$s = extension_loaded('redis');
if ($s) {
    $php_properties['redis'] = array(
        'fitness' => tra('good'),
        'setting' => 'Loaded',
        'message' => tra('This extension can be used to speed up Tiki by saving wiki and forum data on a redis server.')
    );
} else {
    $php_properties['redis'] = array(
        'fitness' => tra('info'),
        'setting' => 'Not available',
        'message' => tra('This extension can be used to speed up Tiki by saving wiki and forum data on a redis server.')
    );
}

$s = extension_loaded('ssh2');
if ($s) {
    $php_properties['SSH2'] = array(
        'fitness' => tra('good'),
        'setting' => 'Loaded',
        'message' => tra('This extension is needed for the show.tiki.org tracker field type, up to Tiki 17.')
    );
} else {
    $php_properties['SSH2'] = array(
        'fitness' => tra('info'),
        'setting' => 'Not available',
        'message' => tra('This extension is needed for the show.tiki.org tracker field type, up to Tiki 17.')
    );
}

$s = extension_loaded('soap');
if ($s) {
    $php_properties['soap'] = array(
        'fitness' => tra('good'),
        'setting' => 'Loaded',
        'message' => tra('This extension is used by Tiki for some types of web services.')
    );
} else {
    $php_properties['soap'] = array(
        'fitness' => tra('info'),
        'setting' => 'Not available',
        'message' => tra('This extension is used by Tiki for some types of web services.')
    );
}

$s = extension_loaded('curl');
if ($s) {
    $php_properties['curl'] = array(
        'fitness' => tra('good'),
        'setting' => 'Loaded',
        'message' => tra('This extension is required for H5P.')
    );
} else {
    $php_properties['curl'] = array(
        'fitness' => tra('bad'),
        'setting' => 'Not available',
        'message' => tra('This extension is required for H5P.')
    );
}

$s = extension_loaded('json');
if ($s) {
    $php_properties['json'] = array(
        'fitness' => tra('good'),
        'setting' => 'Loaded',
        'message' => tra('This extension is required for many features in Tiki.')
    );
} else {
    $php_properties['json'] = array(
        'fitness' => tra('bad'),
        'setting' => 'Not available',
        'message' => tra('This extension is required for many features in Tiki.')
    );
}

$s = extension_loaded('tidy');
if ($s) {
    $php_properties['tidy'] = array(
        'fitness' => tra('good'),
        'setting' => 'Loaded',
        'message' => tra('This extension is required by Tiki PdfGenerator for parsing an html document stored in a string.')
    );
} else {
    $php_properties['tidy'] = array(
        'fitness' => tra('bad'),
        'setting' => 'Not available',
        'message' => tra('This extension is required by Tiki PdfGenerator for parsing an html document stored in a string.')
    );
}

$s = extension_loaded('sodium');
$msg = tra('This extension is required to encrypt data such as CSRF ticket cookie and user data.') . PHP_EOL;
$msg .= tra('Enable safe, encrypted storage of data such as passwords. Since Tiki 22, Sodium lib (included in PHP 7.2 core) is used for the User Encryption feature and improves encryption in other features, when available');
if ($s) {
    $php_properties['sodium'] = array(
        'fitness' => tra('good'),
        'setting' => 'Loaded',
        'message' => $msg
    );
} else {
    $php_properties['sodium'] = array(
        'fitness' => tra('unsure'),
        'setting' => 'Not available',
        'message' => $msg
    );
}

$s = extension_loaded('openssl');
$msg = tra('Enable safe, encrypted storage of data such as passwords. Tiki 21 and earlier versions, require OpenSSL for the User Encryption feature and improves encryption in other features, when available.');
if (! $standalone) {
    $msg .= ' ' . tra('Tiki still uses OpenSSL to decrypt user data encrypted with OpenSSL, when converting that data to Sodium (PHP 7.2+).') . ' ' . tra('Please check the \'User Data Encryption\' section to see if there is user data encrypted with OpenSSL.');
}
if ($s) {
    $php_properties['openssl'] = array(
        'fitness' => tra('good'),
        'setting' => 'Loaded',
        'message' => $msg
    );
} else {
    $php_properties['openssl'] = array(
        'fitness' => tra('unsure'),
        'setting' => 'Not available',
        'message' => $msg
    );
}


$s = extension_loaded('mcrypt');
$msg = tra('MCrypt is abandonware and is being phased out. Starting in version 18 up to 21, Tiki uses OpenSSL where it previously used MCrypt, except perhaps via third-party libraries.');
if (! $standalone) {
    $msg .= ' ' . tra('Tiki still uses MCrypt to decrypt user data encrypted with MCrypt, when converting that data to OpenSSL.') . ' ' . tra('Please check the \'User Data Encryption\' section to see if there is user data encrypted with MCrypt.');
}
if ($s) {
    $php_properties['mcrypt'] = array(
        'fitness' => tra('info'),
        'setting' => 'Loaded',
        'message' => $msg
    );
} else {
    $php_properties['mcrypt'] = array(
        'fitness' => tra('info'),
        'setting' => 'Not available',
        'message' => $msg
    );
}


if (! $standalone) {
    // check Zend captcha will work which depends on \Laminas\Math\Rand
    $captcha = new Laminas\Captcha\Dumb();
    $math_random = array(
        'fitness' => tra('good'),
        'setting' => 'Available',
        'message' => tra('Ability to generate random numbers, useful for example for CAPTCHA and other security features.'),
    );
    try {
        $captchaId = $captcha->getId();    // simple test for missing random generator
    } catch (Exception $e) {
        $math_random['fitness'] = tra('unsure');
        $math_random['setting'] = 'Not available';
    }
    $php_properties['\Laminas\Math\Rand'] = $math_random;
}


$s = extension_loaded('iconv');
$msg = tra('This extension is required and used frequently in validation functions invoked within Zend Framework.');
if ($s) {
    $php_properties['iconv'] = array(
        'fitness' => tra('good'),
        'setting' => 'Loaded',
        'message' => $msg
    );
} else {
    $php_properties['iconv'] = array(
        'fitness' => tra('bad'),
        'setting' => 'Not available',
        'message' => $msg
    );
}

// Check for existence of eval()
// eval() is a language construct and not a function
// so function_exists() doesn't work
$s = eval('return 42;');
if ($s == 42) {
    $php_properties['eval()'] = array(
        'fitness' => tra('good'),
        'setting' => 'Available',
        'message' => tra('The eval() function is required by the Smarty templating engine.')
    );
} else {
    $php_properties['eval()'] = array(
        'fitness' => tra('bad'),
        'setting' => 'Not available',
        'message' => tra('The eval() function is required by the Smarty templating engine.') . ' ' . tra('You will get "Please contact support about" messages instead of modules. eval() is most probably disabled via Suhosin.')
    );
}

// Zip Archive class
$s = class_exists('ZipArchive');
if ($s) {
    $php_properties['ZipArchive class'] = array(
        'fitness' => tra('good'),
        'setting' => 'Available',
        'message' => tra('The ZipArchive class is needed for features such as XML Wiki Import/Export and PluginArchiveBuilder.')
        );
} else {
    $php_properties['ZipArchive class'] = array(
        'fitness' => tra('unsure'),
        'setting' => 'Not Available',
        'message' => tra('The ZipArchive class is needed for features such as XML Wiki Import/Export and PluginArchiveBuilder.')
        );
}

// DateTime class
$s = class_exists('DateTime');
if ($s) {
    $php_properties['DateTime class'] = array(
        'fitness' => tra('good'),
        'setting' => 'Available',
        'message' => tra('The DateTime class is needed for the WebDAV feature.')
        );
} else {
    $php_properties['DateTime class'] = array(
        'fitness' => tra('unsure'),
        'setting' => 'Not Available',
        'message' => tra('The DateTime class is needed for the WebDAV feature.')
        );
}

// Xdebug
$has_xdebug = function_exists('xdebug_get_code_coverage') && is_array(xdebug_get_code_coverage());
if ($has_xdebug) {
    $php_properties['Xdebug'] = array(
        'fitness' => tra('info'),
        'setting' => 'Loaded',
        'message' => tra('Xdebug can be very handy for a development server, but it might be better to disable it when on a production server.')
    );
} else {
    $php_properties['Xdebug'] = array(
        'fitness' => tra('info'),
        'setting' => 'Not Available',
        'message' => tra('Xdebug can be very handy for a development server, but it might be better to disable it when on a production server.')
    );
}

// Get MySQL properties and check them
$mysql_properties = array();
$mysql_variables = array();
if ($connection || ! $standalone) {
    // MySQL version
    $query = 'SELECT VERSION();';
    $result = query($query, $connection) ?? array();
    $mysql_version = $result[0]['VERSION()'];
    $isMariaDB = preg_match('/mariadb/i', $mysql_version);
    $minVersion = $isMariaDB ? '5.5' : '5.7';
    $s = version_compare($mysql_version, $minVersion, '>=');
    $mysql_properties['Version'] = array(
        'fitness' => $s ? tra('good') : tra('bad'),
        'setting' => $mysql_version,
        'message' => tra('Tiki requires MariaDB >= 5.5 or MySQL >= 5.7')
    );

    // max_allowed_packet
    $query = "SHOW VARIABLES LIKE 'max_allowed_packet'";
    $result = query($query, $connection);
    $s = $result[0]['Value'];
    $max_allowed_packet = $s / 1024 / 1024;
    if ($s >= 8 * 1024 * 1024) {
        $mysql_properties['max_allowed_packet'] = array(
            'fitness' => tra('good'),
            'setting' => $max_allowed_packet . 'M',
            'message' => tra('The max_allowed_packet setting is at') . ' ' . $max_allowed_packet . 'M. ' . tra('Quite large files can be uploaded, but keep in mind to set the script timeouts accordingly.') . ' ' . tra('This limits the size of binary files that can be uploaded to Tiki, when storing files in the database. Please see: <a href="http://doc.tiki.org/File-Storage">file storage</a>.')
        );
    } else {
        $mysql_properties['max_allowed_packet'] = array(
            'fitness' => tra('unsure'),
            'setting' => $max_allowed_packet . 'M',
            'message' => tra('The max_allowed_packet setting is at') . ' ' . $max_allowed_packet . 'M. ' . tra('This is not a bad amount, but be sure the level is high enough to accommodate the needs of the site.') . ' ' . tra('This limits the size of binary files that can be uploaded to Tiki, when storing files in the database. Please see: <a href="http://doc.tiki.org/File-Storage">file storage</a>.')
        );
    }

    // UTF-8 MB4 test (required for Tiki19+)
    $query = "SELECT COUNT(*) FROM `information_schema`.`character_sets` WHERE `character_set_name` = 'utf8mb4';";
    $result = query($query, $connection);
    if (! empty($result[0]['COUNT(*)'])) {
        $mysql_properties['utf8mb4'] = array(
            'fitness' => tra('good'),
            'setting' => 'available',
            'message' => tra('Your database supports the utf8mb4 character set required in Tiki19 and above.')
        );
    } else {
        $mysql_properties['utf8mb4'] = array(
            'fitness' => tra('bad'),
            'setting' => 'not available',
            'message' => tra('Your database does not support the utf8mb4 character set required in Tiki19 and above. You need to upgrade your mysql or mariadb installation.')
        );
    }

    // UTF-8 Charset
    // Tiki communication is done using UTF-8 MB4 (required for Tiki19+)
    $charset_types = "client connection database results server system";
    foreach (explode(' ', $charset_types) as $type) {
        $query = "SHOW VARIABLES LIKE 'character_set_" . $type . "';";
        $result = query($query, $connection);
        foreach ($result as $value) {
            if ($value['Value'] == 'utf8mb4') {
                $mysql_properties[$value['Variable_name']] = array(
                    'fitness' => tra('good'),
                    'setting' => $value['Value'],
                    'message' => tra('Tiki is fully utf8mb4 and so should be every part of the stack.')
                );
            } else {
                $mysql_properties[$value['Variable_name']] = array(
                    'fitness' => tra('unsure'),
                    'setting' => $value['Value'],
                    'message' => tra('On a fresh install everything should be set to utf8mb4 to avoid unexpected results. For further information please see <a href="http://doc.tiki.org/Understanding-Encoding">Understanding Encoding</a>.')
                );
            }
        }
    }
    // UTF-8 is correct for character_set_system
    // Because mysql does not allow any config to change this value, and character_set_system is overwritten by the other character_set_* variables anyway. They may change this default in later versions.
    $query = "SHOW VARIABLES LIKE 'character_set_system';";
    $result = query($query, $connection);
    foreach ($result as $value) {
        if (substr($value['Value'], 0, 4) == 'utf8') {
            $mysql_properties[$value['Variable_name']] = array(
                'fitness' => tra('good'),
                'setting' => $value['Value'],
                'message' => tra('Tiki is fully utf8mb4 but some database underlying variables are set to utf8 by the database engine and cannot be modified.')
            );
        } else {
            $mysql_properties[$value['Variable_name']] = array(
                'fitness' => tra('unsure'),
                'setting' => $value['Value'],
                'message' => tra('On a fresh install everything should be set to utf8mb4 or utf8 to avoid unexpected results. For further information please see <a href="http://doc.tiki.org/Understanding-Encoding">Understanding Encoding</a>.')
            );
        }
    }
    // UTF-8 Collation
    $collation_types = "connection database server";
    foreach (explode(' ', $collation_types) as $type) {
        $query = "SHOW VARIABLES LIKE 'collation_" . $type . "';";
        $result = query($query, $connection);
        foreach ($result as $value) {
            if (substr($value['Value'], 0, 7) == 'utf8mb4') {
                $mysql_properties[$value['Variable_name']] = array(
                    'fitness' => tra('good'),
                    'setting' => $value['Value'],
                    'message' => tra('Tiki is fully utf8mb4 and so should be every part of the stack. utf8mb4_unicode_ci is the default collation for Tiki.')
                );
            } else {
                $mysql_properties[$value['Variable_name']] = array(
                    'fitness' => tra('unsure'),
                    'setting' => $value['Value'],
                    'message' => tra('On a fresh install everything should be set to utf8mb4 to avoid unexpected results. utf8mb4_unicode_ci is the default collation for Tiki. For further information please see <a href="http://doc.tiki.org/Understanding-Encoding">Understanding Encoding</a>.')
                );
            }
        }
    }

    // slow_query_log
    $query = "SHOW VARIABLES LIKE 'slow_query_log'";
    $result = query($query, $connection);
    $s = $result[0]['Value'];
    if ($s == 'OFF') {
        $mysql_properties['slow_query_log'] = array(
            'fitness' => tra('info'),
            'setting' => $s,
            'message' => tra('MySQL doesn\'t log slow queries. If performance issues are noticed, this could be enabled, but keep in mind that the logging itself slows MySQL down.')
        );
    } else {
        $mysql_properties['slow_query_log'] = array(
            'fitness' => tra('info'),
            'setting' => $s,
            'message' => tra('MySQL logs slow queries. If no performance issues are noticed, this should be disabled on a production site as it slows MySQL down.')
        );
    }

    // MySQL SSL
    $query = 'show variables like "have_ssl";';
    $result = query($query, $connection);
    if (empty($result)) {
        $query = 'show variables like "have_openssl";';
        $result = query($query, $connection);
    }
    $haveMySQLSSL = false;
    if (! empty($result)) {
        $ssl = $result[0]['Value'];
        $haveMySQLSSL = $ssl == 'YES';
    }
    $s = '';
    if ($haveMySQLSSL) {
        $query = 'show status like "Ssl_cipher";';
        $result = query($query, $connection);
        $isSSL = ! empty($result[0]['Value']);
    } else {
        $isSSL = false;
    }
    if ($isSSL) {
        $msg = tra('MySQL SSL connection is active');
        $s = tra('ON');
    } elseif ($haveMySQLSSL && ! $isSSL) {
        $msg = tra('MySQL connection is not encrypted');
        $s = tra('OFF');
    } else {
        $msg = tra('MySQL Server does not have SSL activated.');
        $s = 'OFF';
    }
    $fitness = tra('info');
    if ($s == tra('ON')) {
        $fitness = tra('good');
    }
    $mysql_properties['SSL connection'] = array(
        'fitness' => $fitness,
        'setting' => $s,
        'message' => $msg
    );

    // Strict mode
    $query = 'SELECT @@sql_mode as Value;';
    $result = query($query, $connection);
    $s = '';
    $msg = 'Unable to query strict mode';
    if (! empty($result)) {
        $sql_mode = $result[0]['Value'];
        $modes = explode(',', $sql_mode);

        if (in_array('STRICT_ALL_TABLES', $modes)) {
            $s = 'STRICT_ALL_TABLES';
        }
        if (in_array('STRICT_TRANS_TABLES', $modes)) {
            if (! empty($s)) {
                $s .= ',';
            }
            $s .= 'STRICT_TRANS_TABLES';
        }

        if (! empty($s)) {
            $msg = tra('MySQL is using strict mode');
        } else {
            $msg = tra('MySQL is not using strict mode.');
        }
    }
    $mysql_properties['Strict Mode'] = array(
        'fitness' => tra('info'),
        'setting' => $s,
        'message' => $msg
    );

    // MySQL Variables
    $query = "SHOW VARIABLES;";
    $result = query($query, $connection) ?? array();
    foreach ($result as $value) {
        $mysql_variables[$value['Variable_name']] = array('value' => $value['Value']);
    }

    if (! $standalone) {
        $mysql_crashed_tables = array();
        // This should give all crashed tables (MyISAM at least) - does need testing though !!
        $query = 'SHOW TABLE STATUS WHERE engine IS NULL AND comment <> "VIEW";';
        $result = query($query, $connection);
        foreach ($result as $value) {
            $mysql_crashed_tables[$value['Name']] = array('Comment' => $value['Comment']);
        }
    }
}

// Apache properties

$apache_properties = false;
if (function_exists('apache_get_version')) {
    // Apache Modules
    $apache_modules = apache_get_modules();

    // mod_rewrite
    $s = false;
    $s = array_search('mod_rewrite', $apache_modules);
    if ($s) {
        $apache_properties = array();
        $apache_properties['mod_rewrite'] = array(
            'setting' => 'Loaded',
            'fitness' => tra('good') ,
            'message' => tra('Tiki needs this module for Search Engine Friendly URLs via .htaccess. However, it can\'t be checked if this web server respects configurations made in .htaccess. For further information go to Admin->SefURL in your Tiki.')
        );
    } else {
        $apache_properties = array();
        $apache_properties['mod_rewrite'] = array(
            'setting' => 'Not available',
            'fitness' => tra('unsure') ,
            'message' => tra('Tiki needs this module for Search Engine Friendly URLs. For further information go to Admin->SefURL in the Tiki.')
        );
    }

    if (! $standalone) {
        // work out if RewriteBase is set up properly
        global $url_path;
        $enabledFileName = '.htaccess';
        if (file_exists($enabledFileName)) {
            $enabledFile = fopen($enabledFileName, "r");
            $rewritebase = '/';
            while ($nextLine = fgets($enabledFile)) {
                if (preg_match('/^RewriteBase\s*(.*)$/', $nextLine, $m)) {
                    $rewritebase = substr($m[1], -1) !== '/' ? $m[1] . '/' : $m[1];
                    break;
                }
            }
            if ($url_path == $rewritebase) {
                $smarty->assign('rewritebaseSetting', $rewritebase);
                $apache_properties['RewriteBase'] = array(
                    'setting' => $rewritebase,
                    'fitness' => tra('good') ,
                    'message' => tra('RewriteBase is set correctly in .htaccess. Search Engine Friendly URLs should work. Be aware, though, that this test can\'t checked if Apache really loads .htaccess.')
                );
            } else {
                $apache_properties['RewriteBase'] = array(
                    'setting' => $rewritebase,
                    'fitness' => tra('bad') ,
                    'message' => tra('RewriteBase is not set correctly in .htaccess. Search Engine Friendly URLs are not going to work with this configuration. It should be set to "') . substr($url_path, 0, -1) . '".'
                );
            }
        } else {
            $apache_properties['RewriteBase'] = array(
                'setting' => tra('Not found'),
                'fitness' => tra('info') ,
                'message' => tra('The .htaccess file has not been activated, so this check cannot be  performed. To use Search Engine Friendly URLs, activate .htaccess by copying _htaccess into its place (or a symlink if supported by your Operating System). Then do this check again.')
            );
        }
    }

    if ($pos = strpos($_SERVER['REQUEST_URI'], 'tiki-check.php')) {
        $sef_test_protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https://' : 'http://';
        $sef_test_base_url = $sef_test_protocol . $_SERVER['HTTP_HOST'] . substr($_SERVER['REQUEST_URI'], 0, $pos);
        $sef_test_ping_value = mt_rand();
        $sef_test_url = $sef_test_base_url . 'tiki-check?tiki-check-ping=' . $sef_test_ping_value;
        $sef_test_folder_created = false;
        $sef_test_folder_writable = true;
        if ($standalone) {
            $sef_test_path_current = __DIR__;
            $sef_test_dir_name = 'tiki-check-' . $sef_test_ping_value;
            $sef_test_folder = $sef_test_path_current . DIRECTORY_SEPARATOR . $sef_test_dir_name;
            if (is_writable($sef_test_path_current) && ! file_exists($sef_test_folder)) {
                if (mkdir($sef_test_folder)) {
                    $sef_test_folder_created = true;
                    copy(__FILE__, $sef_test_folder . DIRECTORY_SEPARATOR . 'tiki-check.php');
                    file_put_contents($sef_test_folder . DIRECTORY_SEPARATOR . '.htaccess', "<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteRule tiki-check$ tiki-check.php [L]\n</IfModule>\n");
                    $sef_test_url = $sef_test_base_url . $sef_test_dir_name . '/tiki-check?tiki-check-ping=' . $sef_test_ping_value;
                }
            } else {
                $sef_test_folder_writable = false;
            }
        }

        if (! $sef_test_folder_writable) {
            $apache_properties['SefURL Test'] = array(
            'setting' => tra('Not Working'),
            'fitness' => tra('info') ,
            'message' => tra('The automated test could not run. The required files could not be created  on the server to run the test. That may only mean that there were no permissions, but the Apache configuration should be checked. For further information go to Admin->SefURL in the Tiki.')
            );
        } else {
            $pong_value = get_content_from_url($sef_test_url);
            if ($pong_value != 'fail-no-request-done') {
                if ('pong:' . $sef_test_ping_value == $pong_value) {
                    $apache_properties['SefURL Test'] = array(
                        'setting' => tra('Working'),
                        'fitness' => tra('good') ,
                        'message' => tra('An automated test was done, and the server appears to be configured correctly to handle Search Engine Friendly URLs.')
                    );
                } else {
                    if (strncmp('fail-http-', $pong_value, 10) == 0) {
                        $apache_return_code = substr($pong_value, 10);
                        $apache_properties['SefURL Test'] = array(
                            'setting' => tra('Not Working'),
                            'fitness' => tra('info') ,
                            'message' => sprintf(tra('An automated test was done and, based on the results, the server does not appear to be configured correctly to handle Search Engine Friendly URLs. The server returned an unexpected HTTP code: "%s". This automated test may fail due to the infrastructure setup, but the Apache configuration should be checked. For further information go to Admin->SefURL in your Tiki.'), $apache_return_code)
                        );
                    } else {
                        $apache_properties['SefURL Test'] = array(
                            'setting' => tra('Not Working'),
                            'fitness' => tra('info') ,
                            'message' => tra('An automated test was done and, based on the results, the server does not appear to be configured correctly to handle Search Engine Friendly URLs. This automated test may fail due to the infrastructure setup, but the Apache configuration should be checked. For further information go to Admin->SefURL in your Tiki.')
                        );
                    }
                }
            }
        }
        if ($sef_test_folder_created) {
            unlink($sef_test_folder . DIRECTORY_SEPARATOR . 'tiki-check.php');
            unlink($sef_test_folder . DIRECTORY_SEPARATOR . '.htaccess');
            rmdir($sef_test_folder);
        }
    }

    // mod_expires
    $s = false;
    $s = array_search('mod_expires', $apache_modules);
    if ($s) {
        $apache_properties['mod_expires'] = array(
            'setting' => 'Loaded',
            'fitness' => tra('good') ,
            'message' => tra('With this module, the HTTP Expires header can be set, which increases performance. It can\'t be checked, though, if mod_expires is configured correctly.')
        );
    } else {
        $apache_properties['mod_expires'] = array(
            'setting' => 'Not available',
            'fitness' => tra('unsure') ,
            'message' => tra('With this module, the HTTP Expires header can be set, which increases performance. Once it is installed, it still needs to be configured correctly.')
        );
    }

    // mod_deflate
    $s = false;
    $s = array_search('mod_deflate', $apache_modules);
    if ($s) {
        $apache_properties['mod_deflate'] = array(
            'setting' => 'Loaded',
            'fitness' => tra('good') ,
            'message' => tra('With this module, the data the webserver sends out can be compressed, which reduced data transfer amounts and increases performance. This test can\'t check, though, if mod_deflate is configured correctly.')
        );
    } else {
        $apache_properties['mod_deflate'] = array(
            'setting' => 'Not available',
            'fitness' => tra('unsure') ,
            'message' => tra('With this module, the data the webserver sends out can be compressed, which reduces data transfer amounts and increases performance. Once it is installed, it still needs to be configured correctly.')
        );
    }

    // mod_security
    $s = false;
    $s = array_search('mod_security', $apache_modules);
    if ($s) {
        $apache_properties['mod_security'] = array(
            'setting' => 'Loaded',
            'fitness' => tra('info') ,
            'message' => tra('This module can increase security of Tiki and therefore the server, but be aware that it is very tricky to configure correctly. A misconfiguration can lead to failed page saves or other hard to trace bugs.')
        );
    } else {
        $apache_properties['mod_security'] = array(
            'setting' => 'Not available',
            'fitness' => tra('info') ,
            'message' => tra('This module can increase security of Tiki and therefore the server, but be aware that it is very tricky to configure correctly. A misconfiguration can lead to failed page saves or other hard to trace bugs.')
        );
    }

    // Get /server-info, if available
    if (function_exists('curl_init') && function_exists('curl_exec')) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'http://localhost/server-info');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        $apache_server_info = curl_exec($curl);
        if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 200) {
            $apache_server_info = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $apache_server_info);
        } else {
            $apache_server_info = false;
        }
        curl_close($curl);
    } else {
        $apache_server_info = 'nocurl';
    }
}


// IIS Properties
$iis_properties = false;

if (check_isIIS()) {
    // IIS Rewrite module
    if (check_hasIIS_UrlRewriteModule()) {
        $iis_properties['IIS Url Rewrite Module'] = array(
            'fitness' => tra('good'),
            'setting' => 'Available',
            'message' => tra('The URL Rewrite Module is required to use SEFURL on IIS.')
            );
    } else {
        $iis_properties['IIS Url Rewrite Module'] = array(
            'fitness' => tra('bad'),
            'setting' => 'Not Available',
            'message' => tra('The URL Rewrite Module is required to use SEFURL on IIS.')
            );
    }
}

// Check Tiki Packages
if (! $standalone) {
    global $tikipath, $base_host;

    $composerManager = new ComposerManager($tikipath);
    $installedLibs = $composerManager->getInstalled() ?: array();

    $packagesToCheck = array(
        array(
            'name' => 'jerome-breton/casperjs-installer',
            'commands' => array(
                'python'
            ),
            'preferences' => array(
                'casperjs_path' => array(
                    'name' => tra('casperjs path'),
                    'type' => 'path'
                )
            ),
        ),
        array(
            'name' => 'media-alchemyst/media-alchemyst',
            'preferences' => array(
                'alchemy_ffmpeg_path' => array(
                    'name' => tra('ffmpeg path'),
                    'type' => 'path'
                ),
                'alchemy_ffprobe_path' => array(
                    'name' => tra('ffprobe path'),
                    'type' => 'path'
                ),
                'alchemy_unoconv_path' => array(
                    'name' => tra('unoconv path'),
                    'type' => 'path'
                ),
                'alchemy_gs_path' => array(
                    'name' => tra('ghostscript path'),
                    'type' => 'path'
                ),
                'alchemy_imagine_driver' => array(
                    'name' => tra('Alchemy Image library'),
                    'type' => 'classOptions',
                    'options' => array(
                        'imagick' => array(
                            'name' => tra('Imagemagick'),
                            'classLib' => 'Imagine\Imagick\Imagine',
                            'className' => 'Imagick',
                            'extension' => false
                        ),
                        'gd' => array(
                            'name' => tra('GD'),
                            'classLib' => 'Imagine\Gd\Imagine',
                            'className' => false,
                            'extension' => 'gd'
                        )
                    ),
                ),
            )
        ),
        array(
            'name' => 'php-unoconv/php-unoconv',
            'preferences' => array(
                'alchemy_unoconv_path' => array(
                    'name' => tra('unoconv path'),
                    'type' => 'path'
                )
            )
        ),
        array(
            'name' => 'mpdf/mpdf',
            'urls' => array(
                $base_host . '/tiki-print.php'
            )
        ),
        array(
            'name' => 'tikiwiki/diagram',
            'urls' => array(
                $prefs['fgal_drawio_service_endpoint']
            )
        )
    );

    $packagesToDisplay = array();
    foreach ($installedLibs as $installedPackage) {
        $key = array_search($installedPackage['name'], array_column($packagesToCheck, 'name'));
        if ($key !== false) {
            $messages = array(
                'successes' => array(),
                'warnings' => array()
            );
            if (isset($packagesToCheck[$key]['preferences'])) {
                $preferenceMessages = checkPreferences($packagesToCheck[$key]['preferences']);
                $messages['successes'] = array_merge($messages['successes'], $preferenceMessages['successes']);
                $messages['warnings'] = array_merge($messages['warnings'], $preferenceMessages['warnings']);
            }
            if (isset($packagesToCheck[$key]['commands'])) {
                foreach ($packagesToCheck[$key]['commands'] as $command) {
                    if (! commandIsAvailable($command)) {
                        $messages['warnings'][] = tr("Command '%0' not found, check if it is installed and available.", $command);
                    } else {
                        $messages['successes'][] = tr("Command '%0' found, it is installed and available.", $command);
                    }
                }
            }
            if (isset($packagesToCheck[$key]['urls'])) {
                foreach ($packagesToCheck[$key]['urls'] as $url) {
                    if (! urlIsAvailable($url)) {
                        $messages['warnings'][] = tr("URL '%0' is not reachable, check your firewall or proxy configurations.", $url);
                    } else {
                        $messages['successes'][] = tr("Command '%0' found, it is installed and available.", $command);
                    }
                }
            }

            $messages = checkPackageMessages($messages, $installedPackage);

            $packageInfo = array(
                'name' => $installedPackage['name'],
                'version' => $installedPackage['installed'],
                'status' => count($messages['warnings']) > 0 ? tra('unsure') : tra('good'),
                'message' => array_merge($messages['warnings'], $messages['successes'])
            );
        } else {
            $packageInfo = array(
                'name' => $installedPackage['name'],
                'version' => $installedPackage['installed'],
                'status' => tra('good'),
                'message' => array()
            );
        }
        $packagesToDisplay[] = $packageInfo;
    }

    /**
     * Tesseract PHP Package Check
     */

    /** @var string The version of Tesseract required */
    $tesseractPkgMinVersion = '2.7.0';
    /** @var string Current Tesseract installed version */
    $ocrVersion = false;
    foreach ($packagesToDisplay as $arrayValue) {
        if ($arrayValue['name'] === 'thiagoalessio/tesseract_ocr') {
            $ocrVersion = $arrayValue['version'];
            break;
        }
    }

    if (! $ocrVersion) {
        $ocrVersion = tra('Not Installed');
        $ocrMessage = tra(
            'Tesseract PHP package could not be found. Try installing through Packages.'
        );
        $ocrStatus = 'bad';
    } elseif (version_compare($ocrVersion, $tesseractPkgMinVersion, '>=')) {
        $ocrMessage = tra('Tesseract PHP dependency installed.');
        $ocrStatus = 'good';
    } else {
        $ocrMessage = tra(
            'The installed Tesseract version is lower than the required version.'
        );
        $ocrStatus = 'bad';
    }

    $ocrToDisplay = array(array(
                         'name'    => tra('Tesseract package'),
                         'version' => $ocrVersion,
                         'status'  => $ocrStatus,
                         'message' => $ocrMessage,
                     ));

    /**
     * Tesseract Binary dependency Check
     */

    $ocr = TikiLib::lib('ocr');
    $langCount = count($ocr->getTesseractLangs());

    if ($langCount >= 5) {
        $ocrMessage = $langCount . ' ' . tra('languages installed.');
        $ocrStatus = 'good';
    } else {
        $ocrMessage = tra(
            'Not all languages installed. You may need to install additional languages for multilingual support.'
        );
        $ocrStatus = 'unsure';
    }

    $ocrToDisplay[] = array(
        'name'    => tra('Tesseract languages'),
        'status'  => $ocrStatus,
        'message' => $ocrMessage,
    );

    $ocrVersion = $ocr->getTesseractVersion();

    if (! $ocrVersion) {
        $ocrVersion = tra('Not Found');
        $ocrMessage = tra(
            'Tesseract could not be found.'
        );
        $ocrStatus = 'bad';
    } elseif ($ocr->checkTesseractVersion()) {
        $ocrMessage = tra(
            'Tesseract meets or exceeds the version requirements.'
        );
        $ocrStatus = 'good';
    } else {
        $ocrMessage = tra(
            'The installed Tesseract version is lower than the required version.'
        );
        $ocrStatus = 'bad';
    }

    $ocrToDisplay[] = array(
        'name'    => tra('Tesseract binary'),
        'version' => $ocrVersion,
        'status'  => $ocrStatus,
        'message' => $ocrMessage,
    );
    try {
        if (empty($prefs['ocr_tesseract_path'])    || $prefs['ocr_tesseract_path'] === 'tesseract') {
            $ocrStatus = 'bad';
            $ocrMessage = tra(
                'Your path preference is not configured. It may work now but will likely fail with cron. Specify an absolute path.'
            );
        } elseif ($prefs['ocr_tesseract_path'] === $ocr->whereIsExecutable('tesseract')) {
            $ocrStatus = 'good';
            $ocrMessage = tra('Path setup correctly.');
        } else {
            $ocrStatus = 'unsure';
            $ocrMessage = tra(
                'Your path may not be configured correctly. It appears to be located at '
            ) . $ocr->whereIsExecutable(
                'tesseract' . '.'
            );
        }
    } catch (Exception $e) {
        if (
            empty($prefs['ocr_tesseract_path'])
            || $prefs['ocr_tesseract_path'] === 'tesseract'
        ) {
            $ocrStatus = 'bad';
            $ocrMessage = tra(
                'Your path preference is not configured. It may work now but will likely fail with cron. Specify an absolute path.'
            );
        } else {
            $ocrStatus = 'unsure';
            $ocrMessage = tra(
                'Your path is configured, but we were unable to tell if it was configured properly or not.'
            );
        }
    }

    $ocrToDisplay[] = array(
        'name'    => tra('Tesseract path'),
        'status'  => $ocrStatus,
        'message' => $ocrMessage,
    );


    $pdfimages = TikiLib::lib('pdfimages');
    $pdfimages->setVersion();

    //lets fall back to configured options for a binary path if its not found with default options.
    if (! $pdfimages->version) {
        $pdfimages->setBinaryPath();
        $pdfimages->setVersion();
    }

    if ($pdfimages->version) {
        $ocrStatus = 'good';
        $ocrMessage = tra('It appears that pdfimages is installed on your system.');
    } else {
        $ocrStatus = 'bad';
        $ocrMessage = tra('Could not find pdfimages. PDF files will not be processed.');
    }

    $ocrToDisplay[] = array(
        'name'    => tra('Pdfimages binary'),
        'version' => $pdfimages->version,
        'status'  => $ocrStatus,
        'message' => $ocrMessage,
    );

    try {
        if (empty($prefs['ocr_pdfimages_path']) || $prefs['ocr_pdfimages_path'] === 'pdfimages') {
            $ocrStatus = 'bad';
            $ocrMessage = tra('Your path preference is not configured. It may work now but will likely fail with cron. Specify an absolute path.');
        } elseif ($prefs['ocr_pdfimages_path'] === $ocr->whereIsExecutable('pdfimages')) {
            $ocrStatus = 'good';
            $ocrMessage = tra('Path setup correctly');
        } else {
            $ocrStatus = 'unsure';
            $ocrMessage = tra('Your path may not be configured correctly. It appears to be located at ') .
                $ocr->whereIsExecutable('pdfimages' . ' ');
        }
    } catch (Exception $e) {
        if (empty($prefs['ocr_pdfimages_path']) || $prefs['ocr_pdfimages_path'] === 'pdfimages') {
            $ocrStatus = 'bad';
            $ocrMessage = tra('Your path preference is not configured. It may work now but will likely fail with cron. Specify an absolute path.');
        } else {
            $ocrStatus = 'unsure';
            $ocrMessage = tra(
                'Your path is configured, but we were unable to tell if it was configured properly or not.'
            );
        }
    }

    $ocrToDisplay[] = array(
        'name'    => tra('Pdfimages path'),
        'status'  => $ocrStatus,
        'message' => $ocrMessage,
    );

    // check if scheduler is set up properly.
    $scheduleDb = $ocr->table('tiki_scheduler');
    $conditions['status'] = 'active';
    $conditions['params'] = $scheduleDb->contains('ocr:all');
    if ($scheduleDb->fetchBool($conditions)) {
        $ocrToDisplay[] = array(
            'name'    => tra('Scheduler'),
            'status'  => 'good',
            'message' => tra('Scheduler has been successfully setup.'),
        );
    } else {
        $ocrToDisplay[] = array(
            'name'    => tra('Scheduler'),
            'status'  => 'bad',
            'message' => tra('Scheduler needs to have a console command of "ocr:all" set.'),
        );
    }

    // Check if PCRE (Perl Compatible Regular Expressions) backtrack_limit
    // is enough higher to allow sufficient attempts
    // when trying to understand a complicated pattern written in a regular expression
    $backtrack_limit = ini_get('pcre.backtrack_limit');
    $url = '<a href="https://doc.tiki.org/Server-Check#OCR_Status_section">doc.tiki.org/Server-Check#OCR_Status_section</a>';
    if ($backtrack_limit !== false) {
        $moreInformation = tr('For more detailed information please check %0', $url);
        if ($backtrack_limit < 1000000) {
            $status = tr('unsure');
            $message = tr('pcre.backtrack_limit is lower that the PHP default of 1000000 in php.ini');
        } else {
            $status = tr('good');
            $message = tr('pcre.backtrack_limit is good.');
        }
        $message .= ' ' . $moreInformation;
        $ocrToDisplay[] = array(
            'name' => tr('PCRE backtrack_limit'),
            'status' => $status,
            'message' => $message
        );
    }

    $smarty->assign('ocr', $ocrToDisplay);
}
// Security Checks
// get all dangerous php settings and check them
$security = false;

// check file upload dir and compare it to tiki root dir
$s = ini_get('upload_tmp_dir');
$sn = substr($_SERVER['SCRIPT_NAME'], 0, -14);
if ($s != "" && strpos($sn, $s) !== false) {
    $security['upload_tmp_dir'] = array(
        'fitness' => tra('unsafe') ,
        'setting' => $s,
        'message' => tra('upload_tmp_dir is probably inside the Tiki directory. There is a risk that someone can upload any file to this directory and access it via web browser.')
    );
} else {
    $security = array();
    $security['upload_tmp_dir'] = array(
        'fitness' => tra('unknown') ,
        'setting' => $s,
        'message' => tra('It can\'t be reliably determined if the upload_tmp_dir is accessible via a web browser. To be sure, check the webserver configuration.')
    );
}

// Determine system state
$pdf_webkit = '';
if (isset($prefs) && $prefs['print_pdf_from_url'] == 'webkit') {
    $pdf_webkit = '<b>' . tra('WebKit is enabled') . '.</b> ';
}
$feature_blogs = '';
if (isset($prefs) && $prefs['feature_blogs'] == 'y') {
    $feature_blogs = '<b>' . tra('The Blogs feature is enabled') . '.</b> ';
}

$fcts = array(
         array(
            'function' => 'exec',
            'risky' => tra('Exec can potentially be used to execute arbitrary code on the server.') . ' ' . tra('Tiki does not need it; perhaps it should be disabled.') . ' ' . tra('However, the Plugins R/RR need it. If you use the Plugins R/RR and the other PHP software on the server can be trusted, this should be enabled.'),
            'safe' => tra('Exec can be potentially be used to execute arbitrary code on the server.') . ' ' . tra('Tiki needs it to run the Plugins R/RR.') . tra('If this is needed and the other PHP software on the server can be trusted, this should be enabled.')
         ),
         array(
            'function' => 'passthru',
            'risky' => tra('Passthru is similar to exec.') . ' ' . tra('Tiki does not need it; perhaps it should be disabled. However, the Composer package manager used for installations in Subversion checkouts may need it.'),
            'safe' => tra('Passthru is similar to exec.') . ' ' . tra('Tiki does not need it; it is good that it is disabled. However, the Composer package manager used for installations in Subversion checkouts may need it.')
         ),
         array(
            'function' => 'shell_exec',
            'risky' => tra('Shell_exec is similar to exec.') . ' ' . tra('Tiki needs it to run PDF from URL: WebKit (wkhtmltopdf). ') . $pdf_webkit . tra('If this is needed and the other PHP software on the server can be trusted, this should be enabled.'),
            'safe' => tra('Shell_exec is similar to exec.') . ' ' . tra('Tiki needs it to run PDF from URL: WebKit (wkhtmltopdf). ') . $pdf_webkit . tra('If this is needed and the other PHP software on the server can be trusted, this should be enabled.')
         ),
         array(
            'function' => 'system',
            'risky' => tra('System is similar to exec.') . ' ' . tra('Tiki does not need it; perhaps it should be disabled.'),
            'safe' => tra('System is similar to exec.') . ' ' . tra('Tiki does not need it; it is good that it is disabled.')
         ),
         array(
            'function' => 'proc_open',
            'risky' => tra('Proc_open is similar to exec.') . ' ' . tra('Tiki does not need it; perhaps it should be disabled. However, the Composer package manager used for installations in Subversion checkouts or when using the package manager from the <a href="https://doc.tiki.org/Packages" target="_blank">admin interface</a> may need it.'),
            'safe' => tra('Proc_open is similar to exec.') . ' ' . tra('Tiki does not need it; it is good that it is disabled. However, the Composer package manager used for installations in Subversion checkouts or when using the package manager from the <a href="https://doc.tiki.org/Packages" target="_blank">admin interface</a> may need it.')
         ),
         array(
            'function' => 'popen',
            'risky' => tra('popen is similar to exec.') . ' ' . tra('Tiki needs it for file search indexing in file galleries. If this is needed and other PHP software on the server can be trusted, this should be enabled.'),
            'safe' => tra('popen is similar to exec.') . ' ' . tra('Tiki needs it for file search indexing in file galleries. If this is needed and other PHP software on the server can be trusted, this should be enabled.')
         ),
         array(
            'function' => 'curl_exec',
            'risky' => tra('Curl_exec can potentially be abused to write malicious code.') . ' ' . tra('Tiki needs it to run features like Kaltura, CAS login and CClite. If these are needed and other PHP software on the server can be trusted, this should be enabled.'),
            'safe' => tra('Curl_exec can potentially be abused to write malicious code.') . ' ' . tra('Tiki needs it to run features like Kaltura, CAS login and CClite. If these are needed and other PHP software on the server can be trusted, this should be enabled.')
         ),
         array(
            'function' => 'curl_multi_exec',
            'risky' => tra('Curl_multi_exec can potentially be abused to write malicious code.') . ' ' . tra('Tiki needs it to run features like Kaltura, CAS login and CClite. If these are needed and other PHP software on the server can be trusted, this should be enabled.'),
            'safe' => tra('Curl_multi_exec can potentially be abused to write malicious code.') . ' ' . tra('Tiki needs it to run features like Kaltura, CAS login and CClite. If these are needed and other PHP software on the server can be trusted, this should be enabled.')
         ),
         array(
            'function' => 'parse_ini_file',
            'risky' => tra('It is probably an urban myth that this is dangerous. Tiki team will reconsider this check, but be warned.') . ' ' . tra('It is required for the <a href="http://doc.tiki.org/System-Configuration" target="_blank">System Configuration</a> feature.'),
            'safe' => tra('It is probably an urban myth that this is dangerous. Tiki team will reconsider this check, but be warned.') . ' ' . tra('It is required for the <a href="http://doc.tiki.org/System-Configuration" target="_blank">System Configuration</a> feature.'),
         )
    );

foreach ($fcts as $fct) {
    if (function_exists($fct['function'])) {
        $security[$fct['function']] = array(
            'setting' => tra('Enabled'),
            'fitness' => tra('risky'),
            'message' => $fct['risky']
        );
    } else {
        $security[$fct['function']] = array(
            'setting' => tra('Disabled'),
            'fitness' => tra('safe'),
            'message' => $fct['safe']
        );
    }
}

// trans_sid
$s = ini_get('session.use_trans_sid');
if ($s) {
    $security['session.use_trans_sid'] = array(
        'setting' => 'Enabled',
        'fitness' => tra('unsafe'),
        'message' => tra('session.use_trans_sid should be off by default. See the PHP manual for details.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} else {
    $security['session.use_trans_sid'] = array(
        'setting' => 'Disabled',
        'fitness' => tra('safe'),
        'message' => tra('session.use_trans_sid should be off by default. See the PHP manual for details.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
}

$s = ini_get('xbithack');
if ($s == 1) {
    $security['xbithack'] = array(
        'setting' => 'Enabled',
        'fitness' => tra('unsafe'),
        'message' => tra('Setting the xbithack option is unsafe. Depending on the file handling of the webserver and the Tiki settings, an attacker may be able to upload scripts to file gallery and execute them.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
} else {
    $security['xbithack'] = array(
        'setting' => 'Disabled',
        'fitness' => tra('safe'),
        'message' => tra('setting the xbithack option is unsafe. Depending on the file handling of the webserver and the Tiki settings,  an attacker may be able to upload scripts to file gallery and execute them.') . ' <a href="#php_conf_info">' . tra('How to change this value') . '</a>'
    );
}

$s = ini_get('allow_url_fopen');
if ($s == 1) {
    $security['allow_url_fopen'] = array(
        'setting' => 'Enabled',
        'fitness' => tra('risky'),
        'message' => tra('allow_url_fopen may potentially be used to upload remote data or scripts. Also used by Composer to fetch dependencies. ' . $feature_blogs . 'If this Tiki does not use the Blogs feature, this can be switched off.')
    );
} else {
    $security['allow_url_fopen'] = array(
        'setting' => 'Disabled',
        'fitness' => tra('safe'),
        'message' => tra('allow_url_fopen may potentially be used to upload remote data or scripts. Also used by Composer to fetch dependencies. ' . $feature_blogs . 'If this Tiki does not use the Blogs feature, this can be switched off.')
    );
}

if ($standalone || (! empty($prefs) && $prefs['fgal_enable_auto_indexing'] === 'y')) {
    // adapted from \FileGalLib::get_file_handlers
    $fh_possibilities = array(
        'application/ms-excel' => array('xls2csv %1'),
        'application/msexcel' => array('xls2csv %1'),
        // vnd.openxmlformats are handled natively in Zend
        //'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => array('xlsx2csv.py %1'),
        'application/ms-powerpoint' => array('catppt %1'),
        'application/mspowerpoint' => array('catppt %1'),
        //'application/vnd.openxmlformats-officedocument.presentationml.presentation' => array('pptx2txt.pl %1 -'),
        'application/msword' => array('catdoc %1', 'strings %1'),
        //'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => array('docx2txt.pl %1 -'),
        'application/pdf' => array('pstotext %1', 'pdftotext %1 -'),
        'application/postscript' => array('pstotext %1'),
        'application/ps' => array('pstotext %1'),
        'application/rtf' => array('catdoc %1'),
        'application/sgml' => array('col -b %1', 'strings %1'),
        'application/vnd.ms-excel' => array('xls2csv %1'),
        'application/vnd.ms-powerpoint' => array('catppt %1'),
        'application/x-msexcel' => array('xls2csv %1'),
        'application/x-pdf' => array('pstotext %1', 'pdftotext %1 -'),
        'application/x-troff-man' => array('man -l %1'),
        'application/zip' => array('unzip -l %1'),
        'text/enriched' => array('col -b %1', 'strings %1'),
        'text/html' => array('elinks -dump -no-home %1'),
        'text/richtext' => array('col -b %1', 'strings %1'),
        'text/sgml' => array('col -b %1', 'strings %1'),
        'text/tab-separated-values' => array('col -b %1', 'strings %1'),
    );

    $fh_native = array(
        'application/pdf' => 18.0,
        'application/x-pdf' => 18.0,
    );

    $file_handlers = array();

    foreach ($fh_possibilities as $type => $options) {
        $file_handler = array(
            'fitness' => '',
            'message' => '',
        );

        if (! $standalone && array_key_exists($type, $fh_native)) {
            if ($tikiWikiVersion->getBaseVersion() >= $fh_native["$type"]) {
                $file_handler['fitness'] = 'good';
                $file_handler['message'] = "will be handled natively";
            }
        }
        if ($standalone && array_key_exists($type, $fh_native)) {
            $file_handler['fitness'] = 'info';
            $file_handler['message'] = "will be handled natively by Tiki &gt;= " . $fh_native["$type"];
        }
        if ($file_handler['fitness'] == '' || $file_handler['fitness'] == 'info') {
            foreach ($options as $opt) {
                $optArray = explode(' ', $opt, 2);
                $exec = reset($optArray);
                $which_exec = `which $exec`;
                if ($which_exec) {
                    if ($file_handler['fitness'] == 'info') {
                        $file_handler['message'] .= ", otherwise handled by $which_exec";
                    } else {
                        $file_handler['message'] = "will be handled by $which_exec";
                    }
                    $file_handler['fitness'] = 'good';
                    break;
                }
            }
            if ($file_handler['fitness'] == 'info') {
                $fh_commands = '';
                foreach ($options as $opt) {
                    $fh_commands .= $fh_commands ? ' or ' : '';
                    $fh_commands .= '"' . substr($opt, 0, strpos($opt, ' ')) . '"';
                }
                $file_handler['message'] .= ', otherwise you need to install ' . $fh_commands . ' to index this type of file';
            }
        }
        if (! $file_handler['fitness']) {
            $file_handler['fitness'] = 'unsure';
            $fh_commands = '';
            foreach ($options as $opt) {
                $fh_commands .= $fh_commands ? ' or ' : '';
                $fh_commands .= '"' . substr($opt, 0, strpos($opt, ' ')) . '"';
            }
            $file_handler['message'] = 'You need to install ' . $fh_commands . ' to index this type of file';
        }
        $file_handlers[$type] = $file_handler;
    }
}


if (! $standalone) {
    // The following is borrowed from tiki-admin_system.php
    $useDatabase = array();
    if ($prefs['feature_forums'] == 'y') {
        $dirs = TikiLib::lib('comments')->list_directories_to_save();
    } else {
        $dirs = array();
    }
    if ($prefs['feature_file_galleries'] == 'y' && ! empty($prefs['fgal_use_dir'])) {
        $dirs[] = $prefs['fgal_use_dir'];
        $useDatabase[] = $prefs['fgal_use_db'];
    }
    if ($prefs['feature_trackers'] == 'y') {
        if (! empty($prefs['t_use_dir'])) {
            $dirs[] = $prefs['t_use_dir'];
            $useDatabase[] = $prefs['t_use_db'];
        }
        $dirs[] = TRACKER_FIELD_IMAGE_STORAGE_PATH;
        $useDatabase[] = ''; //add this to make the array dirs and useDatabase to have the same lenght
    }
    if ($prefs['feature_wiki'] == 'y') {
        if (! empty($prefs['w_use_dir'])) {
            $dirs[] = $prefs['w_use_dir'];
            $useDatabase[] = $prefs['w_use_db'];
        }
        if ($prefs['feature_create_webhelp'] == 'y') {
            $dirs[] = WHELP_PATH;
            $useDatabase[] = '';
        }
        $dirs[] = DEPRECATED_IMG_WIKI_PATH;
        $dirs[] = DEPRECATED_IMG_WIKI_UP_PATH;
        $useDatabase[] = ''; //add this to make the array dirs and useDatabase to have the same lenght
        $useDatabase[] = ''; //add this to make the array dirs and useDatabase to have the same lenght
    }
    $dirs = array_unique($dirs);
    $dirsExist = array();
    foreach ($dirs as $i => $d) {
        $dirsWritable[$i] = is_writable($d);
    }
    $smarty->assign_by_ref('dirs', $dirs);
    $smarty->assign_by_ref('dirsWritable', $dirsWritable);
    $smarty->assign_by_ref('useDatabase', $useDatabase);
    // Prepare Monitoring acks
    $query = "SELECT `value` FROM tiki_preferences WHERE `name`='tiki_check_status'";
    $result = $tikilib->getOne($query);
    $last_state = json_decode($result, true);
    $smarty->assign_by_ref('last_state', $last_state);

    function deack_on_state_change(&$check_group, $check_group_name)
    {
        global $last_state;
        foreach ($check_group as $key => $value) {
            if (! empty($last_state["$check_group_name"]["$key"])) {
                $check_group["$key"]['ack'] = $last_state["$check_group_name"]["$key"]['ack'];
                if (
                    isset($check_group["$key"]['setting']) && isset($last_state["$check_group_name"]["$key"]['setting']) &&
                            $check_group["$key"]['setting'] != $last_state["$check_group_name"]["$key"]['setting']
                ) {
                    $check_group["$key"]['ack'] = false;
                }
            }
        }
    }
    deack_on_state_change($mysql_properties, 'MySQL');
    deack_on_state_change($server_properties, 'Server');
    if ($apache_properties) {
        deack_on_state_change($apache_properties, 'Apache');
    }
    if ($iis_properties) {
        deack_on_state_change($iis_properties, 'IIS');
    }
    deack_on_state_change($php_properties, 'PHP');
    deack_on_state_change($security, 'PHP Security');

    $tikiWikiVersion = new TWVersion();
    if (
        version_compare($tikiWikiVersion->getBaseVersion(), '18.0', '<') && ! class_exists('mPDF')
        || version_compare($tikiWikiVersion->getBaseVersion(), '18.0', '>=') && ! class_exists('\\Mpdf\\Mpdf')
    ) {
        $smarty->assign('mPDFClassMissing', true);
    }

    // Engine tables type
    $db = TikiDb::get();
    if ($db) {
        $engineType = '';
        $query = 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_NAME = "tiki_schema" AND TABLE_SCHEMA = DATABASE();';
        $result = query($query, $connection);
        if (! empty($result[0]['ENGINE'])) {
            $engineType = $result[0]['ENGINE'];
        }
    }
    if (version_compare($tikiWikiVersion->getBaseVersion(), '18.0', '>=') && $db && $engineType != 'InnoDB') {
        $smarty->assign('engineTypeNote', true);
    } else {
        $smarty->assign('engineTypeNote', false);
    }

    //Verify composer and composer install requirements: bzip and unzip bin
    if ($composerAvailable = $composerManager->composerIsAvailable()) {
        $composerChecks['composer'] = array(
            'fitness' => tra('good'),
            'message' => tra('Composer found')
        );
    } else {
        $composerChecks['composer'] = array(
            'fitness' => tra('bad'),
            'message' => tra('Composer not found')
        );
    }

    if (extension_loaded('bz2')) {
        $composerChecks['php-bz2'] = array(
            'fitness' => tra('good'),
            'message' => tra('Extension loaded in PHP')
        );
    } else {
        $composerChecks['php-bz2'] = array(
            'fitness' => tra('bad'),
            'message' => tra('Bz2 extension not loaded in PHP. It may be needed to install composer packages.')
        );
    }

    if (commandIsAvailable('unzip')) {
        $composerChecks['unzip'] = array(
            'fitness' => tra('good'),
            'message' => tra('Command found')
        );
    } else {
        $composerChecks['unzip'] = array(
            'fitness' => tra('unsure'),
            'message' => tra('Command not found. As there is no \'unzip\' command installed zip files are being unpacked using the PHP zip extension.
            This may cause invalid reports of corrupted archives. Besides, any UNIX permissions (e.g. executable) defined in the archives will be lost.')
        );
    }

    $packageRepos = array(
        'composer.tiki.org' => 'https://composer.tiki.org',
        'packagist.org' => 'https://packagist.org'
    );

    foreach ($packageRepos as $key => $url) {
        $isAvailable = urlIsAvailable($url);
        $composerChecks[$key] = array(
            'fitness' => $isAvailable ? tra('good') : tra('unsure'),
            'message' => $isAvailable ? tr("URL '%0' is reachable.", $url) : tr("URL '%0' is not reachable, check your firewall or proxy configurations.", $url)
        );
    }

    $smarty->assign('composer_available', $composerAvailable);
    $smarty->assign('composer_checks', $composerChecks);
    $smarty->assign('packages', $packagesToDisplay);
}

$sensitiveDataDetectedFiles = array();
check_for_remote_readable_files($sensitiveDataDetectedFiles);

if (! empty($sensitiveDataDetectedFiles)) {
    $files = ' (Files: ' . trim(implode(', ', $sensitiveDataDetectedFiles)) . ')';
    $tiki_security['Sensitive Data Exposure'] = array(
        'fitness' => tra('risky'),
        'message' => tra('Tiki detected that there are temporary files in the db folder that may expose credentials or other sensitive information.') . $files
    );
} else {
    $tiki_security['Sensitive Data Exposure'] = array(
        'fitness' => tra('safe'),
        'message' => tra('Tiki did not detect temporary files in the db folder that may expose credentials or other sensitive information.')
    );
}

if (isset($_REQUEST['benchmark'])) {
    $benchmark = BenchmarkPhp::run();
} else {
    $benchmark = '';
}

if (
    isset($_REQUEST["removeTable"]) && $access->checkCsrf(true)
) {
    $checkResult = check_db_mismatches();
    $whiteList = $checkResult['queriedTables'];
    $tableName = $_REQUEST['removeTable'];
    if (in_array($tableName, $whiteList)) {
        $escapedTableName = "`" . $tableName . "`";
         // Drop the table
        $query = "DROP TABLE IF EXISTS $escapedTableName";
        $result = $tikilib->query($query);
        if ($result) {
            echo '<div class="alert alert-info">Table ' . htmlspecialchars($tableName) . ' dropped successfully</div>';
        } else {
            echo '<div class="alert alert-danger">Failed to drop table ' . htmlspecialchars($tableName) . '</div>';
        }
    } else {
        echo '<div class="alert alert-danger">Invalid table\'s name </div>';
    }
}

$diffDatabase = false;
$diffDbTables = array();
$diffDbColumns = array();
$diffFileTables = array();
$diffFileColumns = array();
$dynamicTables = array();
$sqlFileTables = array();

// Get Security token, neccessary for mismatch tables deletion
$ticket = smarty_function_ticket(array('mode' => 'get'), $smarty->getEmptyInternalTemplate());
$smarty->assign('ticket', $ticket);

// Function used to check db mismatches
function check_db_mismatches()
{
    $tikiSql = file_get_contents('db/tiki.sql');
    preg_match_all('/CREATE TABLE (?:.(?!;[^\S]))+./s', $tikiSql, $tables);

    foreach ($tables[0] as $table) {
        preg_match('/CREATE TABLE[\s\t]*`?(\w+)`?/', $table, $matches);
        $tableName = strtolower(trim($matches[1]));
        $sqlFileTables[$tableName] = array();

        preg_match_all('/^[\s\t]*`?(?!CREATE|KEY|PRIMARY|UNIQUE|INDEX)(\w+)`?/m', $table, $fields);

        foreach ($fields[1] as $field) {
            $sqlFileTables[$tableName][] = strtolower($field);
        }
    }

    $query = <<<SQL
    SELECT TABLE_NAME, COLUMN_NAME
    FROM information_schema.columns
    WHERE table_schema = database()
    AND (TABLE_NAME NOT LIKE "index_%" OR TABLE_NAME LIKE "zzz_unused_%");
    SQL;

    $result = query($query);
    $diffFileTables = array_keys($sqlFileTables);
    $diffFileColumns = $sqlFileTables;
    $queriedTables = array();
    $diffDbTables = array();

    foreach ($result as $tables) {
        $dbTable = strtolower($tables['TABLE_NAME']);
        $dbColumn = strtolower($tables['COLUMN_NAME']);

        // Table in DB and SQL
        $key = array_search($dbTable, $diffFileTables);
        if ($key !== false) {
            unset($diffFileTables[$key]);
        }

        // Table in DB but not in SQL file
        if (! array_key_exists($dbTable, $sqlFileTables)) {
            if (! in_array($dbTable, $queriedTables)) {
                // Query to count the number of records in $dbTable
                $recordCountQuery = "SELECT COUNT(*) AS record_count FROM $dbTable";
                $recordCountResult = query($recordCountQuery);
                $recordCount = $recordCountResult[0]['record_count'];

                // Add table name and record count to $diffDbTables
                $diffDbTables[] = array(
                    'tableName' => $dbTable,
                    'tableSize' => $recordCount
                );

                // Add the table to the queriedTables array to avoid duplicate queries
                $queriedTables[] = $dbTable;
            }

            continue;
        }

        // Column in DB but not in SQL file
        if (! in_array($dbColumn, $sqlFileTables[$dbTable])) {
            $diffDbColumns[$dbTable][] = $dbColumn;
        }

        if (isset($diffFileColumns[$dbTable])) {
            $key = array_search($dbColumn, $diffFileColumns[$dbTable]);
            unset($diffFileColumns[$dbTable][$key]);
        }

        if (empty($diffFileColumns[$dbTable])) {
            unset($diffFileColumns[$dbTable]);
        }
    }

    return array(
        'diffFileTables' => $diffFileTables,
        'diffFileColumns' => $diffFileColumns,
        'diffDbTables' => $diffDbTables,
        'queriedTables' => $queriedTables
    );
}

if (isset($_REQUEST['dbmismatches']) && ! $standalone && file_exists('db/tiki.sql')) {
    $diffDatabase = true;
    // Get the db_mismatches check result
    $checkResult = check_db_mismatches();
    $diffFileTables = $checkResult['diffFileTables'];
    $diffFileColumns = $checkResult['diffFileColumns'];
    $diffDbTables = $checkResult['diffDbTables'];

    // If table is missing, then all columns will be missing too (remove from columns diff)
    foreach ($diffFileTables as $table) {
        if (isset($diffFileColumns[$table])) {
            unset($diffFileColumns[$table]);
        }
    }

    $query = <<<SQL
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = database()
  AND TABLE_NAME LIKE "index_%";
SQL;

    $result = query($query);
    foreach ($result as $tables) {
        $dynamicTables[] = $tables['TABLE_NAME'];
    }
}

/**
 * Tiki Manager Section
 **/
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $trimCapable = false;
} else {
    $trimCapable = true;
}

if ($trimCapable) {
    $trimServerRequirements = array();
    $trimClientRequirements = array();

    $trimServerRequirements['Operating System Path'] = array(
        'fitness' => tra('info'),
        'message' => $_SERVER['PATH'] ?? ''
    );

    $trimClientRequirements['Operating System Path'] = array(
        'fitness' => tra('info'),
        'message' => $_SERVER['PATH'] ?? ''
    );

    $trimClientRequirements['SSH or FTP server'] = array(
        'fitness' => tra('info'),
        'message' => tra('To manage this instance from a remote server you need SSH or FTP access to this server')
    );

    $serverCommands = array(
        'php-cli'     => array('command' => 'php'),
        'rsync'       => array('command' => 'rsync'),
        'nice'        => array('command' => 'nice'),
        'tar'         => array('command' => 'tar'),
        'bzip2'       => array('command' => 'bzip2'),
        'ssh'         => array('command' => 'ssh'),
        'ssh-copy-id' => array('command' => 'ssh-copy-id'),
        'scp'         => array('command' => 'scp'),
        'sqlite'      => array(
            'command' => 'sqlite3',
            'message' => 'Command not found, check if it is installed and available in one of the paths above.'
                . ' While this does not impact normal operations, will prevent you to be able to see/debug the'
                . ' internal db using "database:view"',
        ),
    );

    $serverPHPExtensions = array(
        'php-sqlite' => 'sqlite3',
    );

    $clientCommands = array(
        'php-cli' => 'php',
        'mysql' => 'mysql',
        'mysqldump' => 'mysqldump',
        'gzip' => 'gzip',
    );

    foreach ($serverCommands as $key => $commandData) {
        if (commandIsAvailable($commandData['command'])) {
            $trimServerRequirements[$key] = array(
                'fitness' => tra('good'),
                'message' => tra('Command found')
            );
        } else {
            $message = isset($commandData['message'])
                ? $commandData['message']
                : tra('Command not found, check if it is installed and available in one of the paths above.');
            $trimServerRequirements[$key] = array(
                'fitness' => tra('unsure'),
                'message' => $message
            );
        }
    }

    foreach ($serverPHPExtensions as $key => $extension) {
        if (extension_loaded($extension)) {
            $trimServerRequirements[$key] = array(
                'fitness' => tra('good'),
                'message' => tra('Extension loaded in PHP')
            );
        } else {
            $trimServerRequirements[$key] = array(
                'fitness' => tra('unsure'),
                'message' => tra('Extension not loaded in PHP')
            );
        }
    }

    foreach ($clientCommands as $key => $command) {
        if (commandIsAvailable($command)) {
            $trimClientRequirements[$key] = array(
                'fitness' => tra('good'),
                'message' => tra('Command found')
            );
        } else {
            $trimClientRequirements[$key] = array(
                'fitness' => tra('unsure'),
                'message' => tra('Command not found, check if it is installed and available in one of the paths above')
            );
        }
    }
}

$dbEngine = $dbVersion = null;
if ($connection || ! $standalone) {
    $dbEngine = $isMariaDB ? 'mariadb' : 'mysql';
    $dbVersion = ! empty($mysql_properties['Version']['setting']) ? $mysql_properties['Version']['setting'] : null;
} elseif (isset($_POST['db-engine'], $_POST['db-version'])) {
    $dbEngine = $_POST['db-engine'];
    $dbVersion = $_POST['db-version'];
}

$serverRequirements = checkServerRequirements(PHP_VERSION, $dbEngine, $dbVersion);
$available_tiki_properties = getCompatibleVersions($dbEngine, $dbVersion);

if (! $standalone) {
    $serverRequirements['Tiki Version'] = array(
        'value' => $tikiBaseVersion,
        'fitness' => 'info',
        'message' => tra('Current Tiki version'),
    );
} else {
    $recTikiVersion = array_filter($available_tiki_properties, function ($details) {
        return $details['fitness'] == 'good';
    });
    if ($recTikiVersion = reset($recTikiVersion)) {
        $serverRequirements['Tiki Version'] = array(
            'value' => $recTikiVersion['name'],
            'fitness' => 'info',
            'message' => 'Recommended Tiki version',
        );
    } else {
        $serverRequirements['Tiki Version'] = array(
            'value' => 'N/A',
            'fitness' => 'unsure',
            'message' => 'Unable to find a Tiki Version that uses the detected/selected PHP and Database versions.',
        );
    }
}

if ($standalone && ! $nagios) {
    $render .= '<style type="text/css">td, th { border: 1px solid #000000; vertical-align: baseline; padding: .5em; }</style>';
    $render .= '<h2>Server compatibility</h2>';

    renderTable($serverRequirements);

    if (! $locked) {
        if (! $connection) {
            $render .= '<p>Unable to check the server compatibility and the recommended Tiki version.<br>';
            $render .= 'Use the form below to select the Database engine and version, to detect the recommended version.</p>';
            $render .= '<form method="post" action="' . $_SERVER['SCRIPT_NAME'] . '">';
            $render .= '<div class="tiki-form-group mt-3"><label for="db-engine">Database Engine</label>:';
            $render .= '<select name="db-engine" class="form-control">';
            $render .= '<option value="mysql" ' . ($dbEngine == 'mysql' ? 'selected' : '') . '>MySQL</option>';
            $render .= '<option value="mariadb" ' . ($dbEngine == 'mariadb' ? 'selected' : '') . '>MariaDB</option>';
            $render .= '</select></div>';
            $render .= '<div class="tiki-form-group"><label for="db-engine">Database Version</label>: <input type="text"  class="form-control" id="db-version" name="db-version" value="' . $dbVersion . '"/></div>';
            $render .= '<div class="tiki-form-group"><input type="submit" class="btn btn-primary btn-sm" value="Check compatibility" /></div>';
            $render .= '</form>';
        }

        $render .= '<h3>Compatible Tiki Versions</h3>';

        renderAvailableTikiTable($available_tiki_properties);

        $render .= '<h2>MySQL or MariaDB Database Properties</h2>';
        renderTable($mysql_properties);
        $render .= '<h2>Test sending emails</h2>';
        if (isset($_REQUEST['email_test_to'])) {
            $email = filter_var($_POST['email_test_to'], FILTER_SANITIZE_EMAIL);
            $email_test_headers = 'From: noreply@tiki.org' . "\n";    // needs a valid sender
            $email_test_headers .= 'Reply-to: ' . $email . "\n";
            $email_test_headers .= "Content-type: text/plain; charset=utf-8\n";
            $email_test_headers .= 'X-Mailer: Tiki-Check - PHP/' . PHP_VERSION . "\n";
            $email_test_subject = tra('Test mail from Tiki Server Compatibility Test');
            $email_test_body = tra("Congratulations!\n\nThis server can send emails.\n\n");
            $email_test_body .= "\t" . tra('Server:') . ' ' . (empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['SERVER_NAME']) . "\n";
            $email_test_body .= "\t" . tra('Sent:') . ' ' . date(DATE_RFC822) . "\n";

            $sentmail = mail($email, $email_test_subject, $email_test_body, $email_test_headers);
            if ($sentmail) {
                $mail['Sending mail'] = array(
                    'setting' => 'Accepted',
                    'fitness' => tra('good'),
                    'message' => tra('It was possible to send an e-mail. This only means that a mail server accepted the mail for delivery. This check can\;t verify if that server actually delivered the mail. Please check the inbox of ' . htmlspecialchars($email) . ' to see if the mail was delivered.')
                );
            } else {
                $mail['Sending mail'] = array(
                    'setting' => 'Not accepted',
                    'fitness' => tra('bad'),
                    'message' => tra('It was not possible to send an e-mail. It may be that there is no mail server installed on this machine or that it is incorrectly configured. If the local mail server cannot be made to work, a regular mail account can be set up and its SMTP settings configured in tiki-admin.php.')
                );
            }
            renderTable($mail);
        } else {
            $render .= '<form method="post" action="' . $_SERVER['SCRIPT_NAME'] . '">';
            $render .= '<div class="tiki-form-group mt-3"><label for="e-mail">e-mail address to send test mail to</label>: <input type="text"  class="form-control" id="email_test_to" name="email_test_to" /></div>';
            $render .= '<div class="tiki-form-group"><input type="submit" class="btn btn-primary btn-sm" value=" Send e-mail " /></div>';
            $render .= '<p><input type="hidden" id="dbhost" name="dbhost" value="';
            if (isset($_POST['dbhost'])) {
                $render .= htmlentities(strip_tags($_POST['dbhost']), ENT_COMPAT);
            };
                $render .= '" /></p>';
                $render .= '<p><input type="hidden" id="dbuser" name="dbuser" value="';
            if (isset($_POST['dbuser'])) {
                $render .= htmlentities(strip_tags($_POST['dbuser']), ENT_COMPAT);
            };
                $render .= '"/></p>';
                $render .= '<p><input type="hidden" id="dbpass" name="dbpass" value="';
            if (isset($_POST['dbpass'])) {
                $render .= htmlentities(strip_tags($_POST['dbpass']), ENT_COMPAT);
            };
                $render .= '"/></p>';
            $render .= '</form>';
        }
    }

    $render .= '<h2>Server Information</h2>';
    renderTable($server_information);
    $render .= '<h2>Server Properties</h2>';
    renderTable($server_properties);
    $render .= '<h2>Apache properties</h2>';
    if ($apache_properties) {
        renderTable($apache_properties);
        if ($apache_server_info != 'nocurl' && $apache_server_info != false) {
            if (isset($_REQUEST['apacheinfo']) && $_REQUEST['apacheinfo'] == 'y') {
                $render .= $apache_server_info;
            } else {
                $render .= '<a href="' . $_SERVER['SCRIPT_NAME'] . '?apacheinfo=y">Append Apache /server-info;</a>';
            }
        } elseif ($apache_server_info == 'nocurl') {
            $render .= 'You don\'t have the Curl extension in PHP, so we can\'t append Apache\'s server-info.';
        } else {
            $render .= 'Apparently you have not enabled mod_info in your Apache, so we can\'t append more verbose information to this output.';
        }
    } else {
        $render .= 'You are either not running the preferred Apache web server or you are running PHP with a SAPI that does not allow checking Apache properties (for example, CGI or FPM).';
    }
    $render .= '<h2>IIS properties</h2>';
    if ($iis_properties) {
        renderTable($iis_properties);
    } else {
        $render .= tra("You are not running IIS web server.");
    }
    $render .= '<h2>' . tra('PHP scripting language properties') . '</h2>';
    renderTable($php_properties);

    $render_sapi_info = '';
    if (! empty($php_sapi_info)) {
        if (! empty($php_sapi_info['message'])) {
            $render_sapi_info .= $php_sapi_info['message'];
        }
        if (! empty($php_sapi_info['link'])) {
            $render_sapi_info .= '<a href="' . $php_sapi_info['link'] . '"> ' . $php_sapi_info['link'] . '</a>';
        }
        $render_sapi_info = '<p>' . $render_sapi_info . '</p>';
    }

    $render .= tr('Change PHP configuration values:%0 You can check the full documentation on how to change the configurations values in <a href="http://www.php.net/manual/en/configuration.php">http://www.php.net/manual/en/configuration.php</a>', $render_sapi_info);
    $render .= '<h2>' . tra('PHP security properties') . '</h2>';
    renderTable($security);
    $render .= '<h2>' . tra('Tiki Security') . '</h2>';
    renderTable($tiki_security);
    $render .= '<h2>' . tra('MySQL Variables') . '</h2>';
    renderTable($mysql_variables, 'wrap');

    $render .= '<h2>' . tra('File Gallery Search Indexing') . '</h2>';
    $render .= '<em>' . tra('More info') . ' <a href="https://doc.tiki.org/Search-within-files">' . tra('here') . '</a></em>';
    renderTable($file_handlers);

    $render .= '<h2>PHP Info</h2>';
    if (isset($_REQUEST['phpinfo']) && $_REQUEST['phpinfo'] == 'y') {
        ob_start();
        phpinfo();
        $info = ob_get_contents();
        ob_end_clean();
        $info = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $info);
        $render .= $info;
    } else {
        $render .= '<a href="' . $_SERVER['SCRIPT_NAME'] . '?phpinfo=y">Append phpinfo();</a>';
    }

    $render .= '<a name="benchmark"></a><h2>Benchmark PHP/MySQL</h2>';
    $render .= '<a href="tiki-check.php?benchmark=run&ts=' . time() . '#benchmark" style="margin-bottom: 10px;">Check</a>';
    if (! empty($benchmark)) {
        renderTable($benchmark);
    }

    $render .= '<h2>Tiki Manager</h2>';
    $render .= '<em>For more detailed information about Tiki Manager please check <a href="https://doc.tiki.org/Manager">doc.tiki.org/Manager</a></em>.';
    if ($trimCapable) {
        $render .= '<h3>Where Tiki Manager is installed</h3>';
        renderTable($trimServerRequirements);
        $render .= '<h3>Where Tiki instances are installed</h3>';
        renderTable($trimClientRequirements);
    } else {
        $render .= '<p>Apparently Tiki is running on a Windows based server. This feature is not supported natively.</p>';
    }

    createPage('Tiki Server Compatibility', $render);
} elseif ($nagios) {
//  0    OK
//  1    WARNING
//  2    CRITICAL
//  3    UNKNOWN
    $monitoring_info = array( 'state' => 0,
             'message' => '');

    function update_overall_status($check_group, $check_group_name)
    {
        global $monitoring_info;
        $state = 0;
        $message = '';

        foreach ($check_group as $property => $values) {
            if (! isset($values['ack']) || $values['ack'] != true) {
                switch ($values['fitness']) {
                    case 'unsure':
                        $state = max($state, 1);
                        $message .= "$property" . "->unsure, ";
                        break;
                    case 'risky':
                        $state = max($state, 1);
                        $message .= "$property" . "->risky, ";
                        break;
                    case 'bad':
                        $state = max($state, 2);
                        $message .= "$property" . "->BAD, ";
                        break;
                    case 'info':
                        $state = max($state, 3);
                        $message .= "$property" . "->info, ";
                        break;
                    case 'good':
                    case 'safe':
                        break;
                }
            }
        }
        $monitoring_info['state'] = max($monitoring_info['state'], $state);
        if ($state != 0) {
            $monitoring_info['message'] .= $check_group_name . ": " . trim($message, ' ,') . " -- ";
        }
    }

    // Might not be set, i.e. in standalone mode
    if ($mysql_properties) {
        update_overall_status($mysql_properties, "MySQL");
    }
    update_overall_status($server_properties, "Server");
    if ($apache_properties) {
        update_overall_status($apache_properties, "Apache");
    }
    if ($iis_properties) {
        update_overall_status($iis_properties, "IIS");
    }
    update_overall_status($php_properties, "PHP");
    update_overall_status($security, "PHP Security");
    update_overall_status($tiki_security, "Tiki Security");
    $return = json_encode($monitoring_info);
    echo $return;
} else {    // not stand-alone
    if (isset($_REQUEST['acknowledge']) || empty($last_state)) {
        $tiki_check_status = array();
        function process_acks(&$check_group, $check_group_name)
        {
            global $tiki_check_status;
            foreach ($check_group as $key => $value) {
                $formkey = str_replace(array('.',' '), '_', $key);
                if (
                    isset($check_group["$key"]['fitness']) && ($check_group["$key"]['fitness'] === 'good' || $check_group["$key"]['fitness'] === 'safe') ||
                    (isset($_REQUEST["$formkey"]) && $_REQUEST["$formkey"] === "on")
                ) {
                    $check_group["$key"]['ack'] = true;
                } else {
                    $check_group["$key"]['ack'] = false;
                }
            }
            $tiki_check_status["$check_group_name"] = $check_group;
        }
        process_acks($mysql_properties, 'MySQL');
        process_acks($server_properties, 'Server');
        if ($apache_properties) {
            process_acks($apache_properties, "Apache");
        }
        if ($iis_properties) {
            process_acks($iis_properties, "IIS");
        }
        process_acks($php_properties, "PHP");
        process_acks($security, "PHP Security");
        $json_tiki_check_status = json_encode($tiki_check_status);
        $query = "INSERT INTO tiki_preferences (`name`, `value`) values('tiki_check_status', ? ) on duplicate key update `value`=values(`value`)";
        $bindvars = array($json_tiki_check_status);
        $result = $tikilib->query($query, $bindvars);
    }

    $smarty->assign_by_ref('current_tiki_version', $tikiBaseVersion);
    $is_compatible = checkTikiVersionCompatible($available_tiki_properties, $tikiBaseVersion);
    $smarty->assign_by_ref('is_compatible', $is_compatible);
    $smarty->assign_by_ref('server_req', $serverRequirements);
    $smarty->assign_by_ref('is_compatible', $is_compatible);
    $smarty->assign_by_ref('available_tiki_properties', $available_tiki_properties);
    $smarty->assign_by_ref('server_information', $server_information);
    $smarty->assign_by_ref('server_properties', $server_properties);
    $smarty->assign_by_ref('mysql_properties', $mysql_properties);
    $smarty->assign_by_ref('php_properties', $php_properties);
    $smarty->assign_by_ref('php_sapi_info', $php_sapi_info);
    if ($apache_properties) {
        $smarty->assign_by_ref('apache_properties', $apache_properties);
    } else {
        $smarty->assign('no_apache_properties', 'You are either not running the preferred Apache web server or you are running PHP with a SAPI that does not allow checking Apache properties (e.g. CGI or FPM).');
    }
    if ($iis_properties) {
        $smarty->assign_by_ref('iis_properties', $iis_properties);
    } else {
        $smarty->assign('no_iis_properties', tra('You are not running IIS web server.'));
    }
    $smarty->assign_by_ref('security', $security);
    $smarty->assign_by_ref('mysql_variables', $mysql_variables);
    $smarty->assign_by_ref('mysql_crashed_tables', $mysql_crashed_tables);
    if ($prefs['fgal_enable_auto_indexing'] === 'y') {
        $smarty->assign_by_ref('file_handlers', $file_handlers);
    }
    // disallow robots to index page:

    $fmap = array(
        'good' => array('icon' => 'ok', 'class' => 'success'),
        'safe' => array('icon' => 'ok', 'class' => 'success'),
        'bad' => array('icon' => 'ban', 'class' => 'danger'),
        'unsafe' => array('icon' => 'ban', 'class' => 'danger'),
        'risky' => array('icon' => 'warning', 'class' => 'warning'),
        'unsure' => array('icon' => 'warning', 'class' => 'warning'),
        'info' => array('icon' => 'information', 'class' => 'info'),
        'unknown' => array('icon' => 'help', 'class' => 'muted'),
    );
    $smarty->assign('fmap', $fmap);

    if (isset($_REQUEST['bomscanner']) && class_exists('BOMChecker_Scanner')) {
        $timeoutLimit = ini_get('max_execution_time');
        if ($timeoutLimit < 120) {
            set_time_limit(120);
        }

        $BOMScanner = new BOMChecker_Scanner();
        $BOMFiles = $BOMScanner->scan();
        $BOMTotalScannedFiles = $BOMScanner->getScannedFiles();

        $smarty->assign('bom_total_files_scanned', $BOMTotalScannedFiles);
        $smarty->assign('bom_detected_files', $BOMFiles);
        $smarty->assign('bomscanner', true);
    }

    $smarty->assign('trim_capable', $trimCapable);
    if ($trimCapable) {
        $smarty->assign('trim_server_requirements', $trimServerRequirements);
        $smarty->assign('trim_client_requirements', $trimClientRequirements);
    }

    $smarty->assign('sensitive_data_detected_files', $sensitiveDataDetectedFiles);

    $smarty->assign('benchmark', $benchmark);
    $smarty->assign('diffDatabase', $diffDatabase);
    $smarty->assign('diffDbTables', $diffDbTables);
    $smarty->assign('diffDbColumns', $diffDbColumns);
    $smarty->assign('diffFileTables', $diffFileTables);
    $smarty->assign('diffFileColumns', $diffFileColumns);
    $smarty->assign('dynamicTables', $dynamicTables);

    $criptLib = TikiLib::lib('crypt');
    $smarty->assign('user_encryption_stats', array(
        'Sodium' => $criptLib->getUserCryptDataStats('sodium'),
        'OpenSSL' => $criptLib->getUserCryptDataStats('openssl'),
        'MCrypt' => $criptLib->getUserCryptDataStats('mcrypt'),
    ));
    $ws_port = $prefs['realtime_port'] ? $prefs['realtime_port'] : '8080';
    $ws_conn = @fsockopen('localhost', $ws_port);
    if (is_resource($ws_conn)) {
        $ws_listening = true;
        fclose($ws_conn);
    } else {
        $ws_listening = false;
    }
    $realtime = array(
        'feature_enabled' => array(
            'requirement' => tra('Feature enabled'),
            'status' => $prefs['feature_realtime'] === 'y' ? tra('good') : tra('bad'),
            'message' => $prefs['feature_realtime'] === 'y' ? tra('Feature is enabled.') : tra('Feature is disabled in Tiki admin.'),
        ),
        'port_listening' => array(
            'requirement' => tra('Server listening'),
            'status' => $ws_listening ? tra('good') : tra('unsure'),
            'message' => $ws_listening ? tra('Server is listening on local system port ') . $ws_port . '.' : tra('No server found listening on default port ') . $ws_port . tra('. Server might be running on a different port or not running at all.'),
        ),
        'connectivity' => array(
            'requirement' => tra('Connectivity'),
            'status' => 'js',
            'message_good' => tra('Connection to WS server established successfully.'),
            'message_bad' => tra('Could not establish connection to WS server. Check if server is listening and web server proxy configured correctly.'),
        ),
        'message_exchange' => array(
            'requirement' => tra('Message exchange'),
            'status' => 'js',
            'message_good' => tra('Successfully exchanged messages with realtime server.'),
            'message_bad' => tra('Could not exchange messages with realtime server. Check if server is running and configured correctly.'),
        )
    );
    $smarty->assign('realtime', $realtime);
    $smarty->assign('realtime_url', preg_replace('#http://#', 'ws://', preg_replace('#https://#', 'wss://', $base_url)) . 'ws/');

    $output = array();
    $locales = null;
    exec("locale -a 2>&1", $output, $returnCode);
    // Verification of the return code.
    if ($returnCode === 0) {
        // The command was successfully executed, we filter it from the array.
        if (is_array($output)) {
            $locales = array_filter($output);
            sort($locales, SORT_STRING | SORT_FLAG_CASE);
        } else {
            if ($locales = preg_split("/\r ?\n/", $output)) {
                $locales = array_filter($locales);
                sort($locales, SORT_STRING | SORT_FLAG_CASE);
            } else {
                $locales = "Unexpected result";
            }
        }
    } else {
        // The command failed, we take the error array, and convert it to a string to display to the user
        foreach ($output as $errorLine) {
            $locales .= "$errorLine\n";
        }
    }

    $smarty->assign('locales', $locales);

    $smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
    $smarty->assign('mid', 'tiki-check.tpl');
    $smarty->display('tiki.tpl');
}

/**
 * Check package warnings based on specific nuances of each package
 * @param $messages
 * @param $package
 */
function checkPackageMessages($messages, $package)
{
    global $prefs;

    switch ($package['name']) {
        case 'media-alchemyst/media-alchemyst':
            try {
                if (! AlchemyLib::hasReadWritePolicies()) {
                    $messages['warnings'][] = tr(
                        'Alchemy requires "Read" and "Write" policy rights. More info: <a href="%0" target="_blank">%1</a>',
                        'https://doc.tiki.org/tiki-index.php?page=Media+Alchemyst#Document_to_Image_issues',
                        'Media Alchemyst - Document to Image issues'
                    );
                } else {
                    $messages['successes'][] = tr('Alchemy has "Read" and "Write" policy rights.');
                }
            } catch (\Exception $e) {
                $messages['warnings'][] = tr('Error when checking Alchemy "Read" and "Write" policy rights: %0', $e->getMessage());
            }

            if (! UnoconvLib::isPortAvailable()) {
                $messages['warnings'][] = tr(
                    'The configured port (%0) to execute unoconv is in use by another process. The port can be set in \'unoconv port\' preference.',
                    $prefs['alchemy_unoconv_port'] ?: UnoconvLib::DEFAULT_PORT
                );
            } else {
                $messages['successes'][] = tr(
                    'The configured port (%0) is configured to execute unoconv.',
                    $prefs['alchemy_unoconv_port'] ?: UnoconvLib::DEFAULT_PORT
                );
            }
            break;
        default:
            $messages['successes'] = array();
            break;
    }

    return $messages;
}

/**
 * Check if paths set in preferences exist in the system, or if classes exist in project/system
 *
 * @param array $preferences An array with preference key and preference info
 *
 * @return array An array with warning messages.
 */
function checkPreferences(array $preferences)
{
    global $prefs;

    $messages = array(
        'successes' => array(),
        'warnings' => array()
    );

    foreach ($preferences as $prefKey => $pref) {
        if ($pref['type'] == 'path') {
            if (! empty($prefs[$prefKey])) {
                if (! file_exists($prefs[$prefKey])) {
                    $messages['warnings'][] = tr("The path '%0' on preference '%1' does not exist", $prefs[$prefKey], $pref['name']);
                } else {
                    $messages['successes'][] = tr("The path '%0' on preference '%1' exists", $prefs[$prefKey], $pref['name']);
                }
            }
        } elseif ($pref['type'] == 'classOptions') {
            if (isset($prefs[$prefKey])) {
                $options = $pref['options'][$prefs[$prefKey]];

                if (! empty($options['classLib'])) {
                    if (! class_exists($options['classLib'])) {
                        $messages['warnings'][] = tr("The lib '%0' on preference '%1', option '%2' does not exist", $options['classLib'], $pref['name'], $options['name']);
                    } else {
                        $messages['successes'][] = tr("The lib '%0' on preference '%1', option '%2' exists", $options['classLib'], $pref['name'], $options['name']);
                    }
                }

                if (! empty($options['className'])) {
                    if (! class_exists($options['className'])) {
                        $messages['warnings'][] = tr("The class '%0' needed for preference '%1', with option '%2' selected, does not exist", $options['className'], $pref['name'], $options['name']);
                    } else {
                        $messages['successes'][] = tr("The class '%0' needed for preference '%1', with option '%2' selected, exists", $options['className'], $pref['name'], $options['name']);
                    }
                }

                if (! empty($options['extension'])) {
                    if (! extension_loaded($options['extension'])) {
                        $messages['warnings'][] = tr("The extension '%0' on preference '%1', with option '%2' selected, is not loaded", $options['extension'], $pref['name'], $options['name']);
                    } else {
                        $messages['successes'][] = tr("The extension '%0' on preference '%1', with option '%2' selected, is loaded", $options['extension'], $pref['name'], $options['name']);
                    }
                }
            }
        }
    }

    return $messages;
}

/**
 * Check if a given command can be located in the system
 *
 * @param $command
 * @return bool true if available, false if not.
 */
function commandIsAvailable($command)
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $template = "where %s";
    } else {
        $template = "command -v %s 2>/dev/null";
    }

    $returnCode = '';
    if (function_exists('exec')) {
        exec(sprintf($template, escapeshellarg($command)), $output, $returnCode);
    }

    return $returnCode === 0 ? true : false;
}

/**
 * Check if a given url can be reach from the system
 *
 * @param string $url
 * @return bool true if available, false if not.
 */
function urlIsAvailable($url)
{
    $client = TikiLib::lib('tiki')->get_http_client($url);
    $response = $client->getResponse();

    return $response && $response->getStatusCode();
}

/**
 * Script to benchmark PHP and MySQL
 * @see https://github.com/odan/benchmark-php
 */
class BenchmarkPhp
{
    /**
     * Executes the benchmark and returns an array in the format expected by renderTable
     * @return array Benchmark results
     */
    public static function run()
    {
        set_time_limit(120); // 2 minutes

        $options = array();

        if (file_exists('db/local.php')) {
            require 'db/local.php';
            $options['db.host'] = $host_tiki;
            $options['db.user'] = $user_tiki;
            $options['db.pw'] = $pass_tiki;
            $options['db.name'] = $dbs_tiki;
        }

        $benchmarkResult = self::test_benchmark($options);

        $benchmark = $benchmarkResult['benchmark'];
        if (isset($benchmark['mysql'])) {
            foreach ($benchmark['mysql'] as $k => $v) {
                $benchmark['mysql.' . $k] = $v;
            }
            unset($benchmark['mysql']);
        }
        $benchmark['total'] = $benchmarkResult['total'];
        $benchmark = array_map(
            function ($v) {
                return array('value' => $v);
            },
            $benchmark
        );

        return $benchmark;
    }

    /**
     * Execute the benchmark
     * @param $settings database connection settings
     * @return array Benchmark results
     */
    protected static function test_benchmark($settings)
    {
        $timeStart = microtime(true);

        $result = array();
        $result['version'] = '1.1';
        $result['sysinfo']['time'] = date("Y-m-d H:i:s");
        $result['sysinfo']['php_version'] = PHP_VERSION;
        $result['sysinfo']['platform'] = PHP_OS;
        $result['sysinfo']['server_name'] = $_SERVER['SERVER_NAME'];
        $result['sysinfo']['server_addr'] = $_SERVER['SERVER_ADDR'];

        self::test_math($result);
        self::test_string($result);
        self::test_loops($result);
        self::test_ifelse($result);
        if (isset($settings['db.host']) && function_exists('mysqli_connect')) {
            self::test_mysql($result, $settings);
        }

        $result['total'] = self::timer_diff($timeStart);
        return $result;
    }

    /**
     * Benchmark the execution of multiple math functions
     * @param $result Benchmark results
     * @param int $count Number of iterations
     */
    protected static function test_math(&$result, $count = 400000)
    {
        $timeStart = microtime(true);

        for ($i = 0; $i < $count; $i++) {
            sin($i);
            asin($i);
            cos($i);
            acos($i);
            tan($i);
            atan($i);
            abs($i);
            floor($i);
            exp($i);
            is_finite($i);
            is_nan($i);
            sqrt($i);
            log10($i);
        }
        $result['benchmark']['math'] = self::timer_diff($timeStart);
    }

    /**
     * Benchmark the execution of multiple string functions
     * @param $result Benchmark results
     * @param int $count Number of iterations
     */
    protected static function test_string(&$result, $count = 400000)
    {
        $timeStart = microtime(true);

        $string = 'the quick brown fox jumps over the lazy dog';
        for ($i = 0; $i < $count; $i++) {
            addslashes($string);
            chunk_split($string);
            metaphone($string);
            strip_tags($string);
            md5($string);
            sha1($string);
            strtoupper($string);
            strtolower($string);
            strrev($string);
            strlen($string);
            soundex($string);
            ord($string);
        }
        $result['benchmark']['string'] = self::timer_diff($timeStart);
    }

    /**
     * Benchmark the execution of loops
     * @param $result Benchmark results
     * @param int $count Number of iterations
     */
    protected static function test_loops(&$result, $count = 4000000)
    {
        $timeStart = microtime(true);
        for ($i = 0; $i < $count; ++$i) {
        }
        $i = 0;
        while ($i < $count) {
            ++$i;
        }
        $result['benchmark']['loops'] = self::timer_diff($timeStart);
    }

    /**
     * Benchmark the execution of conditional operators
     * @param $result Benchmark results
     * @param int $count Number of iterations
     */
    protected static function test_ifelse(&$result, $count = 4000000)
    {
        $timeStart = microtime(true);
        for ($i = 0; $i < $count; $i++) {
            if ($i == -1) {
            } elseif ($i == -2) {
            } else {
                if ($i == -3) {
                }
            }
        }
        $result['benchmark']['ifelse'] = self::timer_diff($timeStart);
    }

    /**
     * Benchmark MySQL operations
     * @param $result Benchmark results
     * @param $settings MySQL connection information
     * @return array
     */
    protected static function test_mysql(&$result, $settings)
    {
        $timeStart = microtime(true);

        $link = mysqli_connect($settings['db.host'], $settings['db.user'], $settings['db.pw']);
        $result['benchmark']['mysql']['connect'] = self::timer_diff($timeStart);

        mysqli_select_db($link, $settings['db.name']);
        $result['benchmark']['mysql']['select_db'] = self::timer_diff($timeStart);

        $dbResult = mysqli_query($link, 'SELECT VERSION() as version;');
        $arr_row = mysqli_fetch_array($dbResult);
        $result['sysinfo']['mysql_version'] = $arr_row['version'];
        $result['benchmark']['mysql']['query_version'] = self::timer_diff($timeStart);

        $query = "SELECT BENCHMARK(1000000,ENCODE('hello',RAND()));";
        $dbResult = mysqli_query($link, $query);
        $result['benchmark']['mysql']['query_benchmark'] = self::timer_diff($timeStart);

        mysqli_close($link);

        $result['benchmark']['mysql']['total'] = self::timer_diff($timeStart);
        return $result;
    }

    /**
     * Helper to calculate time elapsed
     * @param $timeStart time to compare against now
     * @return string time elapsed
     */
    protected static function timer_diff($timeStart)
    {
        return number_format(microtime(true) - $timeStart, 3);
    }
}

/**
 * Identify files, like backup copies made by editors, or manual copies of the local.php files,
 * that may be accessed remotely and, because they are not interpreted as PHP, may expose the source,
 * which might contain credentials or other sensitive information.
 * Ref: http://feross.org/cmsploit/
 *
 * @param array $files Array of filenames. Suspicious files will be added to this array.
 * @param string $sourceDir Path of the directory to check
 */
function check_for_remote_readable_files(array &$files, $sourceDir = 'db')
{
    //fix dir slash
    $sourceDir = str_replace('\\', '/', $sourceDir);

    if (substr($sourceDir, -1, 1) != '/') {
        $sourceDir .= '/';
    }

    if (! is_dir($sourceDir)) {
        return;
    }

    $sourceDirHandler = opendir($sourceDir);

    if ($sourceDirHandler === false) {
        return;
    }

    while ($file = readdir($sourceDirHandler)) {
        // Skip ".", ".."
        if ($file == '.' || $file == '..') {
            continue;
        }

        $sourceFilePath = $sourceDir . $file;

        if (is_dir($sourceFilePath)) {
            check_for_remote_readable_files($files, $sourceFilePath);
        }

        if (! is_file($sourceFilePath)) {
            continue;
        }

        $pattern = '/(^#.*#|~|.sw[op])$/';
        preg_match($pattern, $file, $matches);

        if (! empty($matches[1])) {
            $files[] = $file;
            continue;
        }

        // Match "local.php.bak", "local.php.bck", "local.php.save", "local.php." or "local.txt", for example
        $pattern = '/local(?!.*[.]php$).*$/'; // The negative lookahead prevents local.php and other files which will be interpreted as PHP from matching.
        preg_match($pattern, $file, $matches);

        if (! empty($matches[0])) {
            $files[] = $file;
            continue;
        }
    }
}

function check_isIIS()
{
    static $IIS;
    // Sample value Microsoft-IIS/7.5
    if (! isset($IIS) && isset($_SERVER['SERVER_SOFTWARE'])) {
        $IIS = substr($_SERVER['SERVER_SOFTWARE'], 0, 13) == 'Microsoft-IIS';
    }
    return $IIS;
}

function check_hasIIS_UrlRewriteModule()
{
    return isset($_SERVER['IIS_UrlRewriteModule']) == true;
}

function get_content_from_url($url)
{
    if (function_exists('curl_init') && function_exists('curl_exec')) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        if (isset($_SERVER) && isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            curl_setopt($curl, CURLOPT_USERPWD, $_SERVER['PHP_AUTH_USER'] . ":" . $_SERVER['PHP_AUTH_PW']);
        }
        $content = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($http_code != 200) {
            $content = "fail-http-" . $http_code;
        }
        curl_close($curl);
    } else {
        $content = "fail-no-request-done";
    }
    return $content;
}

function no_cache_found()
{
    global $php_properties;

    if (check_isIIS()) {
        $php_properties['ByteCode Cache'] = array(
            'fitness' => tra('info'),
            'setting' => 'N/A',
            'message' => tra('WinCache is being used as the ByteCode Cache; if one of these were used and correctly configured, performance would be increased. See Admin->Performance in the Tiki for more details.')
        );
    } else {
        $php_properties['ByteCode Cache'] = array(
            'fitness' => tra('info'),
            'setting' => 'N/A',
            'message' => tra('OPcache is being used as the ByteCode Cache; if one of these were used and correctly configured, performance would be increased. See Admin->Performance in the Tiki for more details.')
        );
    }
}
