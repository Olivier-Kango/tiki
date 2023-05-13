<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\MailIn\Action;

use Tiki\MailIn\Account;
use Tiki\MailIn\Source\Message;
use TikiLib;

class WikiPrepend extends WikiPut
{
    public function getName()
    {
        return tr('Wiki Prepend');
    }

    protected function handleContent($data, $info)
    {
        if ($info) {
            return $data['body'] . "\n" . $info['data'];
        } else {
            return $data['body'];
        }
    }
}
