<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Wiki\SlugManager;

class UrlencodeGenerator implements Generator
{
    public function getName()
    {
        return 'urlencode';
    }

    public function getLabel()
    {
        return tr('URL Encode (Tiki Classic)');
    }

    public function generate($pageName, $suffix = null)
    {
        return urlencode($pageName) . $suffix;
    }

    public function degenerate($slug)
    {
        return urldecode($slug);
    }
}
