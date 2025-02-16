<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class Query extends Base
{
    public function handle($params, Template $template)
    {
        global $auto_query_args, $prefs;
        $smarty = \TikiLib::lib('smarty');
        static $request = null;

        // Modify explicit params to be prefixed if they need to (used in a plugin, module, ...)
        if (! empty($smarty->url_overriding_prefix)) {
            foreach ($smarty->url_overriding_prefix[1] as $v) {
                if (isset($params[$v])) {
                    $params[$smarty->url_overriding_prefix[0] . $v] = $params[$v];
                    unset($params[$v]);
                }
            }
        }

        if (isset($params['_noauto']) && $params['_noauto'] == 'y') {
            $query = [];
            foreach ($params as $param_name => $param_value) {
                if ($param_name[0] == '_' || $param_value == 'NULL' || $param_value == null) {
                    continue;
                }
                $query[$param_name] = $param_value;
            }
            // Even if _noauto is set, 'filegals_manager' is a special param that has to be kept all the time
            if (! isset($params['filegals_manager']) && isset($_REQUEST['filegals_manager'])) {
                $query['filegals_manager'] = $_REQUEST['filegals_manager'];
            }
            // Even if _noauto is set, 'insertion_syntax' is a special param that has to be kept all the time
            if (! isset($params['insertion_syntax']) && isset($_REQUEST['insertion_syntax'])) {
                $query['insertion_syntax'] = $_REQUEST['insertion_syntax'];
            }
        } else {
            // Not using _REQUEST here, because it is sometimes directly modified in scripts
            if ($request === null) {
                // make a copy of the $_GET and $_POST arrays as it seems php7 assigns these by reference now
                // and so they get directly modified below instead of just the query string being set
                $request = [];
                if (! empty($_GET)) {
                    foreach ($_GET as $k => $v) {
                        $request[$k] = $v;
                    }
                }
                if (! empty($_POST)) {
                    foreach ($_POST as $k => $v) {
                        $request[$k] = $v;
                    }
                }
            }
            $query = $request;

            if (is_array($params)) {
                foreach ($params as $param_name => $param_value) {
                    // Arguments starting with an underscore are special and must not be included in URL
                    if ($param_name[0] == '_') {
                        continue;
                    }
                    if ($param_name == 'page') {
                        $list = [$param_value];
                    } else {
                        $list = explode(",", $param_value ?? "");
                    }
                    if ($param_name == 'sort_mode') {
                        // multi-column sort has comma-separated values in the request
                        if (isset($_REQUEST[$param_name]) && (in_array($_REQUEST[$param_name], $list) || array_intersect(explode(',', $_REQUEST[$param_name]), $list))) {
                            $request_values = explode(',', $_REQUEST[$param_name]);
                        } elseif (isset($query[$param_name]) && (in_array($query[$param_name], $list) || array_intersect(explode(',', $query[$param_name]), $list))) {
                            $request_values = explode(',', $query[$param_name]);
                        } else {
                            $request_values = [];
                        }
                        $final_value = null;
                        foreach ($request_values as $value) {
                            if (in_array($value, $list)) {
                                $final_value = $list[(array_search($value, $list) + 1) % count($list)];
                            }
                        }
                        if ($final_value) {
                            $query[$param_name] = $final_value;
                        } else {
                            $query[$param_name] = $list[0];
                        }
                        if ($list[0] === null || $list[0] == 'NULL') {
                            unset($query[$param_name]);
                        }
                    } else {
                        if (isset($_REQUEST[$param_name]) and in_array($_REQUEST[$param_name], $list)) {
                            $query[$param_name] = $list[(array_search($_REQUEST[$param_name], $list) + 1) % count($list)];
                            if ($query[$param_name] === null or $query[$param_name] == 'NULL') {
                                unset($query[$param_name]);
                            }
                        } elseif (isset($query[$param_name]) and in_array($query[$param_name], $list)) {
                            $query[$param_name] = $list[(array_search($query[$param_name], $list) + 1) % count($list)];
                            if ($query[$param_name] === null or $query[$param_name] == 'NULL') {
                                unset($query[$param_name]);
                            }
                        } else {
                            if ($list[0] !== null and $list[0] != 'NULL') {
                                $query[$param_name] = $list[0];
                            } else {
                                unset($query[$param_name]);
                            }
                        }
                    }
                }
            }
        }

        if (is_array($query)) {
            // Only keep params explicitely specified when calling this function or specified in the $auto_query_args global var
            // This is to avoid including unwanted params (like actions : remove, save...)
            if ((! isset($params['_keepall']) || $params['_keepall'] != 'y') && is_array($auto_query_args)) {
                foreach ($query as $k => $v) {
                    if (! in_array($k, $auto_query_args) && ! (is_array($params) && array_key_exists($k, $params))) {
                        unset($query[$k]);
                    }
                }
            }

            $ret = '';
            if (isset($params['_type']) && $params['_type'] == 'form_input') {
                foreach ($query as $k => $v) {
                    $rtag = '<input type="hidden"';
                    $rname = htmlspecialchars($k, ENT_QUOTES, 'UTF-8');
                    if (is_array($v)) {
                        foreach ($v as $vk => $vv) {
                            $vrname = $rname . '[' . htmlspecialchars($vk, ENT_QUOTES, 'UTF-8') . ']';
                            if (is_array($vv)) {    // handle nested array values
                                foreach ($vv as $vvk => $vvv) {
                                    $vrname2 = $vrname . '[' . htmlspecialchars($vvk, ENT_QUOTES, 'UTF-8') . ']';
                                    $ret .= $rtag . ' name="' . $vrname2 . '" value="' . htmlspecialchars($vvv, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
                                }
                            } else {
                                $ret .= $rtag . ' name="' . $vrname . '" value="' . htmlspecialchars($vv, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
                            }
                        }
                    } else {
                        $ret .= $rtag . ' name="' . $rname . '" value="' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
                    }
                }
            } else {
                if (isset($params['controller'], $params['action']) && $prefs['feature_sefurl'] == 'y') {
                    unset($query['controller'], $query['action']);
                }
                if (! isset($params['_urlencode'])) {
                    $params['_urlencode'] = 'y';
                }
                $sep = $params['_urlencode'] == 'n' ? '&' : '&amp;';
                $ret = http_build_query($query, '', $sep);
            }
        }

        if (is_array($params) && isset($params['_type'])) {
            global $base_host;

            // Check for anchor used as script
            if (! empty($params['_script']) && $params['_script'][0] == '#') {
                if (empty($params['_anchor'])) {
                    $params['_anchor'] = substr($params['_script'], 1);
                }
                if (empty($params['_anchor'])) {
                    $params['_type'] = 'anchor';
                }
                unset($params['_script']);
            }

            // If specified, use _script argument to determine the php script to link to
            // ... else, use PHP_SELF server var
            $php_self = '';

            if (isset($params['_script']) && $params['_script'] != '') {
                $php_self = $params['_script'];

                // If _script does not already specifies the directory and if there is one in PHP_SELF server var, use it
                if ($php_self != 'javascript:void(0)' && strpos($php_self, '/') === false && $_SERVER['PHP_SELF'][0] == '/' && stripos($params['_script'], 'mailto:') !== 0) {
                    $php_self = str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])) . '/' . $php_self;
                }
            } elseif (empty($params['_anchor']) || ! empty($ret)) {
                // Use current script explicitely, except if there is only an anchor (i.e. no script and no URL argument) which is enough
                // This also implies that if no anchor, every current URL params will be loosed

                if (isset($params['controller'], $params['action'])) {
                    $php_self = smarty_function_service(
                        [
                            'controller' => $params['controller'],
                            'action' => $params['action'],
                        ],
                        $template
                    );
                } else {
                    if ($_SERVER['PHP_SELF'] == 'tiki-ajax_services.php' && isset($_GET['controller'], $_GET['action'])) {
                        $php_self = smarty_function_service(
                            [
                                'controller' => $_GET['controller'],
                                'action' => $_GET['action'],
                            ],
                            $template
                        );
                    } else {
                        $php_self = htmlspecialchars($_SERVER['PHP_SELF']);
                    }
                }
            } else {
                // If we just have an anchor, return only this anchor, usual types other than 'anchor' are irrelevant
                $params['_type'] = 'anchor';
            }
            if (! empty($php_self)) {
                if (basename($php_self) === 'route.php') {
                    global $inclusion;
                    $php_self = str_replace('route.php', $inclusion, $php_self);
                } elseif (basename($php_self) === 'tiki-ajax_services.php' && ! empty($_SERVER['HTTP_REFERER'])) {
                    //$php_self = str_replace('tiki-ajax_services.php', basename($_SERVER['HTTP_REFERER']), $php_self); // TODO: figure out if this was really needed for anything as it was wrongly replacing the link with the last visited referrer in the PluginH5P Edit button
                    $pos = strpos($php_self, '?');
                    if ($pos !== false) {
                        $php_self = substr($php_self, 0, $pos);
                    }
                }
            }

            if (strpos($php_self, '/') === 0) {
                $php_self = "/" . ltrim($php_self, "/");
            }
            switch ($params['_type']) {
                case 'absolute_uri':
                    $ret = $base_host . $php_self . ($ret == '' ? '' : '?' . $ret);
                    break;
                case 'absolute_path':
                    $ret = $php_self . ($ret == '' ? '' : '?' . $ret);
                    break;
                case 'relative':
                    $ret = basename($php_self) . ($ret == '' ? '' : '?' . $ret);
                    break;
                case 'form_input':
                case 'arguments':
                case 'anchor': /* default */
            }
        }

        if (isset($params['_anchor'])) {
            $ret .= '#' . $params['_anchor'];
        }

        if ($prefs['feature_sefurl'] == 'y') {
            include_once('tiki-sefurl.php');
            $ret = filter_out_sefurl($ret);
        }

        return $ret;
    }
}
