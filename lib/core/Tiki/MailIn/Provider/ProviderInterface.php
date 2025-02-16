<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\MailIn\Provider;

interface ProviderInterface
{
    public function isEnabled();
    public function getType();
    public function getLabel();
    public function getActionFactory(array $acc);
}
