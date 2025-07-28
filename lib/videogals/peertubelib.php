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

        $response = $this->makeRequest('POST', $url, $data, false);
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
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        // Enable verbose logging only in debug mode
        global $prefs;
        if (! empty($prefs['debug']) && $prefs['debug'] === 'y') {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
        }

        // Prepare headers
        $headers = ['Accept: application/json'];
        if ($method === 'POST' && is_array($data) && isset($data['videofile'])) {
            // Multipart request (e.g., file upload)
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            // Standard POST or other methods
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                $postFields = $rawBody ? $data : http_build_query($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            }
        }

        // Add authentication header if required
        if ($auth) {
            try {
                $token = $this->getAccessToken();
                if (empty($token)) {
                    throw new \Exception('Authentication token is empty');
                }
                $headers[] = 'Authorization: Bearer ' . $token;
            } catch (\Exception $e) {
                error_log("Authentication error: " . $e->getMessage());
                throw new \Exception('Failed to obtain access token: ' . $e->getMessage());
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            if (isset($verbose)) {
                rewind($verbose);
                $verboseLog = stream_get_contents($verbose);
                fclose($verbose);
                error_log("cURL verbose log: $verboseLog");
            }
            curl_close($ch);
            throw new \Exception("cURL error: $error");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (isset($verbose)) {
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);
            fclose($verbose);
            if (! empty($verboseLog)) {
                error_log("cURL verbose log: $verboseLog");
            }
        }

        if ($httpCode >= 400) {
            error_log("HTTP error $httpCode: $response");
            throw new \Exception("HTTP error $httpCode");
        }

        return $response;
    }

    public function uploadVideo($file, $metadata)
    {
        global $prefs;

        $url = rtrim($prefs['peertube_service_url'], '/') . '/api/v1/videos/upload';

        $data = [
            'channelId' => $metadata['channelId'],
            'name' => $metadata['name'],
            'videofile' => $file['videofile'],
        ];

        if (isset($metadata['description'])) {
            $data['description'] = $metadata['description'];
        }

        if (isset($metadata['categories'])) {
            $data['category'] = $metadata['categories'];
        }

        if (isset($metadata['privacy'])) {
            $data['privacy'] = $metadata['privacy'];
        }

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

        $id = trim($id);

        if (empty($id)) {
            throw new \Exception("Video ID cannot be empty");
        }

        $url = rtrim($prefs['peertube_service_url'], '/') . "/api/v1/videos/" . urlencode($id);
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

    public function getMyAccountName(): string
    {
        global $prefs;

        $url = rtrim($prefs['peertube_service_url'], '/') . '/api/v1/users/me';

        $response = $this->makeRequest('GET', $url);
        $data = json_decode($response, true);

        if (! isset($data['account']) || ! isset($data['account']['name'])) {
            throw new \Exception('Could not retrieve account name from PeerTube');
        }

        return $data['account']['name'];
    }

    public function getMyChannels(): array
    {
        global $prefs;

        if (empty($prefs['peertube_service_url'])) {
            throw new \Exception('Missing PeerTube service URL.');
        }

        $accountName = $this->getMyAccountName();

        $url = rtrim($prefs['peertube_service_url'], '/') . '/api/v1/accounts/' . urlencode($accountName) . '/video-channels';

        return json_decode($this->makeRequest('GET', $url), true);
    }

    public function testConnection(): bool
    {
        try {
            global $prefs;
            $url = rtrim($prefs['peertube_service_url'], '/') . '/api/v1/config';
            $response = $this->makeRequest('GET', $url);
            $data = json_decode($response, true);
            return ! empty($data['instance']);
        } catch (\Exception $e) {
            return false;
        }
    }
}
