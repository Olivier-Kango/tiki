<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_ApiClient
{
    protected $url;
    protected $isTiki;
    protected $apiBridge;

    public function __construct($url, $isTiki = true)
    {
        $this->url = $url;
        $this->isTiki = $isTiki;
        $this->apiBridge = new Services_ApiBridge();
    }

    public function __call($method, $args)
    {
        $endpoint = $args[0] ?? '';
        $arguments = $args[1] ?? [];

        $client = $this->getClient($method, $endpoint, $arguments);

        $headers = $client->getRequest()->getHeaders();
        $headers->addHeaders(['Accept' => 'application/json']);

        $response = $client->send();
        if (! $response->isSuccess()) {
            $body = json_decode($response->getBody());
            if ($body->message) {
                $error = $body->message;
            } else {
                $key = key($body);
                $error = $body->$key;
            }
            throw new Services_Exception(tr('Remote service inaccessible (%0), error: "%1"', $response->getStatusCode(), $error), 400);
        }

        return json_decode($response->getBody(), true);
    }

    public function getResultLoader($endpoint, $arguments = [], $offsetKey = 'offset', $maxRecordsKey = 'maxRecords', $resultKey = 'result', $perPage = 20)
    {
        $client = $this->getClient('get', $endpoint, $arguments);
        return new Services_ResultLoader(
            [new Services_ResultLoader_WebService($client, $offsetKey, $maxRecordsKey, $resultKey), '__invoke'],
            $perPage
        );
    }

    public function route($name, $args = [])
    {
        return $this->apiBridge->generateRoute($name, $args);
    }

    private function getClient($method, $endpoint, $arguments)
    {
        $tikilib = TikiLib::lib('tiki');
        if ($this->isTiki) {
            $url = $this->url . '/tiki-api.php?route=' . $endpoint;
        } else {
            $url = $this->url;
            if ($endpoint) {
                $url .= '/' . $endpoint;
            }
        }
        $client = $tikilib->get_http_client($url);
        switch ($method) {
            case 'get':
                $client->setMethod(Laminas\Http\Request::METHOD_GET);
                $client->setParameterGet(array_merge(
                    $client->getRequest()->getQuery()->getArrayCopy(),
                    $arguments
                ));
                break;
            case 'post':
                $client->setMethod(Laminas\Http\Request::METHOD_POST);
                $client->setParameterPost(array_merge(
                    $client->getRequest()->getPost()->getArrayCopy(),
                    $arguments
                ));
                break;
            case 'patch':
                $client->setMethod(Laminas\Http\Request::METHOD_PATCH);
                $client->setParameterPost(array_merge(
                    $client->getRequest()->getPost()->getArrayCopy(),
                    $arguments
                ));
                break;
            case 'delete':
                $client->setMethod(Laminas\Http\Request::METHOD_DELETE);
                break;
            default:
                throw new Services_Exception(tr('Remove service invalid method used: %0, endpoint: %1', $method, $endpoint));
        }
        return $client;
    }
}
