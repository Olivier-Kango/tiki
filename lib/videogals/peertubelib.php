<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Videogals;

class PeerTubeLib
{
    private $sessionType;
    private $initialized = false;

    public function __construct($session_type = 0)
    {
        $this->sessionType = $session_type;
    }

    public function testSetup()
    {
        global $prefs;
        return ! empty($prefs['peertube_service_url'])
            && ! empty($prefs['peertube_client_id'])
            && ! empty($prefs['peertube_client_secret'])
            && ! empty($prefs['peertube_username'])
            && ! empty($prefs['peertube_password']);
    }

    public function getAccessToken()
    {
        global $prefs, $user;
        $session = "peertube_session_{$this->sessionType}_$user";

        if (isset($_SESSION[$session]) && $_SESSION[$session]['expiry'] > \TikiLib::lib('tiki')->now) {
            return $_SESSION[$session]['token'];
        }

        $url = rtrim($prefs['peertube_service_url'], '/') . '/api/v1/users/token';

        $data = [
            'client_id' => $prefs['peertube_client_id'],
            'client_secret' => $prefs['peertube_client_secret'],
            'grant_type' => 'password',
            'username' => $prefs['peertube_username'],
            'password' => $prefs['peertube_password'],
        ];

        $response = $this->makeRequest('POST', $url, http_build_query($data, '', '&', PHP_QUERY_RFC3986), false, true);
        $token = json_decode($response, true);

        if (! isset($token['access_token']) || ! isset($token['expires_in'])) {
            throw new \Exception('Invalid token response from PeerTube');
        }

        $_SESSION[$session] = [
            'token' => $token['access_token'],
            'expiry' => \TikiLib::lib('tiki')->now + $token['expires_in'],
        ];

        return $token['access_token'];
    }

    private function makeRequest($method, $url, $data = [], $auth = true, $rawBody = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $headers = ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'];
        if ($auth) {
            $headers[] = 'Authorization: Bearer ' . $this->getAccessToken();
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            $postFields = $rawBody ? $data : http_build_query($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("cURL error: $error");
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        fclose($verbose);

        curl_close($ch);

        if ($http_code >= 400) {
            throw new \Exception("HTTP error $http_code: $body");
        }

        return $body;
    }

    public function uploadVideo($file, $metadata)
    {
        global $prefs;

        $url = rtrim($prefs['peertube_service_url'], '/') . '/api/v1/videos/upload';

        $data = [
            'channelId' => $prefs['peertube_channel_id'],
            'name' => $metadata['name'],
            'description' => $metadata['description'],
        ];

        $cfile = new \CURLFile($file['tmp_name'], $file['type'], $file['name']);
        $data['videofile'] = $cfile;

        return json_decode($this->makeRequest('POST', $url, $data), true);
    }

    public function listVideos($sort_mode = 'createdAt_desc', $page = 1, $page_size = 10, $find = '')
    {
        global $prefs;
        $url = rtrim($prefs['peertube_service_url'], '/') . "/api/v1/videos?start=" . (($page - 1) * $page_size) . "&count=$page_size&sort=$sort_mode";

        if ($find) {
            $url .= "&search=" . urlencode($find);
        }

        $response = $this->makeRequest('GET', $url);
        return json_decode($response);
    }

    public function getVideo($id)
    {
        global $prefs;
        $url = rtrim($prefs['peertube_service_url'], '/') . "/api/v1/videos/$id";
        $response = $this->makeRequest('GET', $url);
        return json_decode($response, true);
    }

    public function deleteVideo($id)
    {
        global $prefs;
        $url = rtrim($prefs['peertube_service_url'], '/') . "/api/v1/videos/$id";
        $response = $this->makeRequest('DELETE', $url);
        return json_decode($response, true);
    }

    public function updateVideo($id, array $data)
    {
        global $prefs;
        $url = rtrim($prefs['peertube_service_url'], '/') . "/api/v1/videos/$id";
        $response = $this->makeRequest('PUT', $url, $data);
        return json_decode($response, true);
    }

    public function getVideoList(array $videoIds)
    {
        $videos = [];
        foreach ($videoIds as $id) {
            $videos[] = $this->getVideo($id);
        }
        return $videos;
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->makeRequest('GET', '/api/v1/config');
            $data = json_decode($response, true);
            return isset($data['instance']);
        } catch (\Exception $e) {
            return false;
        }
    }
}
