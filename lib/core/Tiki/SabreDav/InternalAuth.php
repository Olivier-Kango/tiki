<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\SabreDav;

use Sabre\DAV\Auth\Backend\BackendInterface;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Always-on authentication backend class.
 *
 * This class ensures current Tiki global $user
 * is always authenticated without specifying HTTP
 * basic or other type of auth headers. Useful when
 * running SabreDav server from within Tiki itself.
 */
class InternalAuth implements BackendInterface
{
    public function check(RequestInterface $request, ResponseInterface $response)
    {
        global $user;
        return [true, 'principals/' . $user];
    }

    public function challenge(RequestInterface $request, ResponseInterface $response)
    {
        // noop
    }
}
