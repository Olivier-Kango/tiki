<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Search\Manticore;

use Manticoresearch\Exceptions\ExceptionInterface as ManticoreException;

class ClientException extends Exception
{
    protected $context;

    public function __construct(ManticoreException $e)
    {
        $this->context = [];
        if (method_exists($e, 'getRequest') && $e->getRequest()) {
            $this->context['request'] = $e->getRequest()->toArray();
        }
        if (method_exists($e, 'getResponse') && $e->getResponse()) {
            $this->context['response'] = $e->getResponse()->getResponse();
        }
        parent::__construct($e->getMessage(), $e->getCode(), $e);
    }

    public function getContext()
    {
        return $this->context;
    }
}
