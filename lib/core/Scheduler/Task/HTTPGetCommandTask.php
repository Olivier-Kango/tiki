<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Scheduler_Task_HTTPGetCommandTask extends Scheduler_Task_CommandTask
{
    public function execute($params = null)
    {
        try {
            if (! array_key_exists('url', $params)) {
                $this->errorMessage = tra('Missing the URL to call.');

                return false;
            }

            if (! array_key_exists('output_file', $params)) {
                $this->errorMessage = tra('Missing the file path to where the output should be saved.');

                return false;
            }

            $url = $params['url'];
            $output = $params['output_file'];

            $validationResult = $this->validateOutputFile($output);
            if ($validationResult !== true) {
                $this->errorMessage = is_string($validationResult)
                    ? $validationResult
                    : tra('Invalid output file.');

                return false;
            }

            $this->logger->debug(sprintf(tra('URL to invoke: %s'), $url));
            $this->logger->debug(sprintf(tra('Save output to: %s'), $output));

            $tikilib = TikiLib::lib('tiki');
            $client = $tikilib->get_http_client($url);

            if (! empty($params['basic_auth_username']) && ! empty($params['basic_auth_password'])) {
                $client->setAuth($params['basic_auth_username'], $params['basic_auth_password']);
                $this->logger->debug(tra('Using basic authentication'));
            }

            $additionalHeaders = trim($params['additional_http_headers']);

            if (! empty($additionalHeaders)) {
                $additionalHeaders = explode("\n", $additionalHeaders);
                $additionalHeaders = array_map(function ($value) {
                    return trim($value);
                }, $additionalHeaders);
                $this->logger->debug(tra('Including additional headers'));
                $client->setHeaders($additionalHeaders);
            }

            $this->logger->debug(tra('Sending request'));
            $response = $client->send();

            if ($response->isSuccess()) {
                $this->logger->debug(tra('Request was successful'));
                $fp = fopen($output, "w");
                if (! $fp) {
                    $this->errorMessage = sprintf(tra('Failed to open file %s to write.'), $output);

                    return false;
                }

                fwrite($fp, $response->getContent());
                fclose($fp);

                return true;
            } else {
                $this->logger->debug(tra('Request failed'));
                $this->errorMessage = $response->getReasonPhrase();

                return false;
            }
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            return false;
        }
    }

    public function getParams()
    {
        return [
            'url' => [
                'name' => tra('URL'),
                'type' => 'text',
                'required' => true,
            ],
            'output_file' => [
                'name' => tra('Output File'),
                'description' => tra('File path to save the output'),
                'type' => 'text',
                'required' => true,
            ],
            'additional_http_headers' => [
                'name' => tra('Additional HTTP Headers'),
                'description' => tra('One HTTP Header per line'),
                'type' => 'textarea',
            ],
            'basic_auth_username' => [
                'name' => tra('Auth Username'),
                'type' => 'text',
            ],
            'basic_auth_password' => [
                'name' => tra('Auth Password'),
                'type' => 'password',
            ],
        ];
    }

    public function validateOutputFile($value): string | bool
    {
        if (! is_string($value) || $value === '' || str_contains($value, "\0")) {
            return tra('You cannot write to this path.');
        }

        $allowedRoots = [TEMP_PATH, HTTP_PUBLIC_PATH];
        $pathDir = dirname($value);
        $realPathDir = realpath($pathDir);

        if ($realPathDir === false) {
            return tra('You cannot write to this path.');
        }

        $isAllowedPath = false;
        foreach ($allowedRoots as $dir) {
            $realDir = realpath($dir);
            if ($realDir === false) {
                continue;
            }

            if ($realPathDir === $realDir || str_starts_with($realPathDir, $realDir . DIRECTORY_SEPARATOR)) {
                $isAllowedPath = true;
                break;
            }
        }

        if (! $isAllowedPath) {
            return tra('You cannot write to this path.');
        }

        $allowedExts = [
            'txt', 'log', 'json', 'csv', 'xml', 'md', 'yml', 'yaml',
        ];

        $extension = strtolower(pathinfo($value, PATHINFO_EXTENSION));

        if ($extension === '' || ! in_array($extension, $allowedExts, true)) {
            return tra('This extension is not allowed.');
        }

        return true;
    }
}
