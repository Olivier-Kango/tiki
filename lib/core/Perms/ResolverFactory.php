<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
interface Perms_ResolverFactory
{
    public function getHash(array $context);
    public function getResolver(array $context);
    public function bulk(array $baseContext, $bulkKey, array $values);
}
