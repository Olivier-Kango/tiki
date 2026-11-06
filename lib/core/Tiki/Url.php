<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki;

/**
 * Class Url
 */
class Url
{
    /**
     * Resolve the public origin once for both page bases and redirects.
     * SERVER_PORT describes the backend connection. Forwarded headers describe
     * the public connection only when feature_port_rewriting explicitly trusts
     * a proxy; that proxy must replace client-supplied forwarded headers.
     * Configured ports are also used when switching to the other protocol.
     */
    public static function publicOrigin(array $server, array $prefs): array
    {
        $httpPort = (int) (($prefs['http_port'] ?? '') ?: 80);
        $httpsPort = (int) (($prefs['https_port'] ?? '') ?: 443);
        $backendPort = (int) ($server['SERVER_PORT'] ?? 80);
        $https = ($server['HTTPS'] ?? '') === 'on'
            || $backendPort === $httpsPort
            || ($prefs['https_login'] ?? '') === 'force_nocheck';
        $proxy = ($prefs['feature_port_rewriting'] ?? 'n') === 'y'
            && in_array($server['HTTP_X_FORWARDED_PROTO'] ?? '', ['http', 'https'], true);
        $host = $prefs['server_domain'] ?? '';
        $authority = $host ?: ($server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? '');
        $parts = $authority !== '' ? parse_url('http://' . $authority) : [];
        $host = $parts['host'] ?? '';
        if ($proxy) {
            $https = $server['HTTP_X_FORWARDED_PROTO'] === 'https';
            // Never infer a public proxy port from the backend socket.
            $publicPort = $https ? $httpsPort : $httpPort;
            $forwardedPort = filter_var($server['HTTP_X_FORWARDED_PORT'] ?? '', FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 65535],
            ]);
            if ($forwardedPort !== false) {
                $publicPort = $forwardedPort;
            } elseif (isset($parts['port'])) {
                $publicPort = $parts['port'];
            }
        } else {
            $publicPort = $parts['port'] ?? $backendPort;
            // HTTPS may be asserted by the web server or force_nocheck while
            // PHP receives HTTP:80. Keep an explicit public Host port intact.
            if ($https && $publicPort === 80 && ! isset($parts['port'])) {
                $publicPort = $httpsPort;
            }
        }
        if ($https) {
            $httpsPort = $publicPort;
        } else {
            $httpPort = $publicPort;
        }
        return [
            'scheme' => $https ? 'https' : 'http',
            'host' => $host,
            'port' => ($https ? $httpsPort === 443 : $httpPort === 80) ? '' : $publicPort,
            'http_port' => $httpPort === 80 ? '' : $httpPort,
            'https_port' => $httpsPort === 443 ? '' : $httpsPort,
        ];
    }

    /**
     * Handle the fallback url pref, in case the info couldn't be guessed from the request (eg. when called from cli)
     *
     * @return bool|array false if didn't process, array with the values written otherwise
     */
    public static function handleFallbackUrl()
    {
        global $prefs, $https_mode, $url_scheme, $url_host, $url_port, $url_path, $base_host, $base_url, $base_url_http, $base_url_https, $tikiroot;

        if (! empty($url_host) || empty($prefs['fallbackBaseUrl'])) {
            return false;
        }

        $values = static::parseFallbackUrl($prefs['fallbackBaseUrl']);

        if ($values == false) {
            return false;
        }

        $url_scheme = $values['url_scheme'];
        $url_port = $values['url_port'];
        $https_mode = $values['https_mode'];
        $url_host = $values['url_host'];
        $url_path = $values['url_path'];
        $tikiroot = $values['tikiroot'];
        $base_host = $values['base_host'];
        $base_url = $values['base_url'];
        $base_url_http = $values['base_url_http'];
        $base_url_https = $values['base_url_https'];

        return $values;
    }

    /**
     * Parses the fallback url in the different values of the tiki url used when building urls
     *
     * @param string $url the URL to process
     * @return bool|array false if url couldn't be parsed, array of values if successful
     */
    public static function parseFallbackUrl($url)
    {
        global $prefs;

        if (empty($url)) {
            return false;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return false;
        }

        $options['url_scheme'] = 'http';
        $options['url_port'] = $prefs['http_port'];
        $options['https_mode'] = false;
        if (isset($parts['scheme']) && $parts['scheme'] == 'https') {
            $options['url_scheme'] = 'https';
            $options['url_port'] = $prefs['https_port'];
            $options['https_mode'] = true;
        }

        $options['url_host'] = '';
        if (isset($parts['host'])) {
            $options['url_host'] = $parts['host'];
        }

        if (isset($parts['port'])) {
            $options['url_port'] = $parts['port'];
            if ($options['url_scheme'] == 'http') {
                $prefs['http_port'] = $parts['port'];
            } else {
                $prefs['https_port'] = $parts['port'];
            }
        }

        $options['tikiroot'] = empty($parts['path']) ? '/' : $parts['path'];
        $options['url_path'] = $options['tikiroot'];


        $options['base_host'] = $options['url_scheme'] . '://' . $options['url_host'] . ((isset($parts['port'])) ? ':' . $parts['port'] : '');
        $options['base_url'] = $options['url_scheme'] . '://' . $options['url_host'] . ((isset($parts['port'])) ? ':' . $parts['port'] : '') . $options['url_path'];
        $options['base_url_http'] = 'http://' . $options['url_host'] . (($prefs['http_port'] != '') ? ':' . $prefs['http_port'] : '') . $options['url_path'];
        $options['base_url_https'] = 'https://' . $options['url_host'] . (($prefs['https_port'] != '') ? ':' . $prefs['https_port'] : '') . $options['url_path'];

        return $options;
    }
}
