<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

use Tiki\Sections;

global $https_mode, $url_scheme, $url_host, $url_port, $url_path, $base_host, $base_url, $base_url_http, $base_url_https, $tikiroot;

/*
 * The current preferences dictate that:
 * - reverse proxy support must be explicitly enabled.
 * - non-standard ports must be configured in $prefs['http_port'] and $prefs['https_port'].
 *
 * Technically, one could check for a reverse proxy header and then decide whether
 * to enable or disable reverse proxy support. This implementation follows the
 * documented preference definitions:
 * - reverse proxy support must be enabled with $prefs['feature_port_rewriting'].
 * - the port for the requested protocol is detected and overrides its preference.
 * - the non-standard HTTP port must be configured in $prefs['http_port'] to
 *   allow switching protocols when the request uses the wrong protocol.
 * - the non-standard HTTPS port must be configured in $prefs['https_port'] to
 *   allow switching protocols when the request uses the wrong protocol.
 */
$prefs['http_port'] = (int) ($prefs['http_port'] ?? 80);
$prefs['https_port'] = (int) ($prefs['https_port'] ?? 443);

// These globals describe the public URL, never the backend proxy connection.
$publicOrigin = \Tiki\Url::publicOrigin($_SERVER, $prefs);
$url_scheme = $publicOrigin['scheme'];
$https_mode = $url_scheme === 'https';
$url_host = $publicOrigin['host'];
$url_port = $publicOrigin['port'];
$url_path = $tikiroot;
if ($url_host) {
    $base_host = $url_scheme . '://' . $url_host . (($url_port != '') ? ':' . $url_port : '');
    $base_url = $url_scheme . '://' . $url_host . (($url_port != '') ? ':' . $url_port : '') . $url_path;
    $base_url_http = 'http://' . $url_host . (($publicOrigin['http_port'] != '') ? ':' . $publicOrigin['http_port'] : '') . $url_path;
    $base_url_https = 'https://' . $url_host . (($publicOrigin['https_port'] != '') ? ':' . $publicOrigin['https_port'] : '') . $url_path;
} else {
    $base_host = '';
    $base_url = '';
    $base_url_http = '';
    $base_url_https = '';
}

// for <base> tag, which needs the "absolute URI that acts as the base URI for resolving relative URIs", not just the root of the site
// however, if the actual path/request_uri contains a slash (i.e. via a rewrite rule in htaccess) then use the real script name
if (! empty($_SERVER['REQUEST_URI']) && strpos(substr($_SERVER['REQUEST_URI'], strlen($tikiroot)), '/') === false) {
    $base_uri = $base_host . $_SERVER['REQUEST_URI'];
} elseif (! empty($_SERVER['SCRIPT_NAME'])) {
    $base_uri = $base_host . $_SERVER['SCRIPT_NAME'];
    if (! empty($_SERVER['QUERY_STRING'])) {
        $base_uri .= '?' . str_replace('?', '&', $_SERVER['QUERY_STRING']);
    } else {
        $base_uri .= '?' . http_build_query($_GET);
    }
} else {
    $base_uri = $base_host; // maybe better than nothing
}

if (str_contains($base_uri, $tikiroot . 'route.php') && ! empty($inclusion)) {
    $base_uri = $base_url . $inclusion;
    if (! empty($_GET)) {
        $base_uri .= '?' . http_build_query($_GET, '', '&');
    }
    global $sections;

    Sections::onSectionChange(function ($section) use ($sections, &$base_uri) {
        if (isset($sections[$section]['objectType'])) {
            $objectType = $sections[$section]['objectType'];
        } else {
            $objectType = null;
        }
        if ($objectType === Sections::SECTION_WIKI_PAGE) {
            $objectType = Sections::SECTION_WIKI;
        }
        include_once('tiki-sefurl.php');
        $base_uri = TikiLib::tikiUrlOpt(filter_out_sefurl($base_uri, $objectType));
    });
}

// SSL options

if (isset($_REQUEST['stay_in_ssl_mode_present']) || isset($_REQUEST['stay_in_ssl_mode'])) {
    // We stay in HTTPS / SSL mode if 'stay_in_ssl_mode' has an 'y' or 'on' value
    $stay_in_ssl_mode = (
            (isset($_REQUEST['stay_in_ssl_mode']) && $_REQUEST['stay_in_ssl_mode'] == 'y')
            || (isset($_REQUEST['stay_in_ssl_mode']) && $_REQUEST['stay_in_ssl_mode'] == 'on' )
            )
            ? 'y'
            : 'n';
} else {
    // Set default value of 'stay_in_ssl_mode' to the current mode state
    $stay_in_ssl_mode = $https_mode ? 'y' : 'n';
}

// Show the 'Stay in SSL mode' checkbox only if we are already in HTTPS
$show_stay_in_ssl_mode = $https_mode || $prefs['https_login'] == 'required' ? 'y' : 'n';

\Tiki\Url::handleFallbackUrl();
