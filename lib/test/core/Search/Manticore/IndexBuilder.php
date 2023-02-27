<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Search\Manticore;

trait IndexBuilder
{
    protected function getIndex($suffix = '')
    {
        $dsn = empty(getenv('MANTICORE_DSN')) ? 'http://127.0.0.1' : getenv('MANTICORE_DSN');
        $http_port = empty(getenv('MANTICORE_HTTP_PORT')) ? '9308' : getenv('MANTICORE_HTTP_PORT');
        $mysql_port = empty(getenv('MANTICORE_MYSQL_PORT')) ? '9306' : getenv('MANTICORE_MYSQL_PORT');

        try {
            $http_client = new \Search_Manticore_Client($dsn, $http_port);
            $mysql_client = new \Search_Manticore_PdoClient($dsn, $mysql_port);
        } catch (\Search_Manticore_Exception $e) {
            $this->markTestSkipped('Manticore needs to be available on ' . $dsn . ':' . $mysql_port . ' for the test to run. Error: ' . $e->getMessage());
        }

        $status = $mysql_client->getStatus();
        if (! isset($status['version'])) {
            $this->markTestSkipped('Manticore needs to be available on ' . $dsn . ':' . $mysql_port . ' for the test to run.');
        }

        return new \Search_Manticore_Index($http_client, $mysql_client, 'test_index' . $suffix);
    }
}
