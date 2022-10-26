<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery\DirectMapping;

use TikiLib;

class Utilities
{
    public static function getRoot($info)
    {
        $root = $info;
        self::traverse($info, true, function ($info) use (&$root) {
            $root = $info;
        });
        return $root;
    }

    public static function getRootConfig($info)
    {
        $config = [];
        self::traverse($info, true, function ($info) use (&$config) {
            $config = $info['direct'] ?? [];
        });
        return $config;
    }

    public static function getVirtualPath($info)
    {
        $path = '';
        self::traverse($info, false, function ($info) use (&$path) {
            if ($path) {
                $path = '/' . $path;
            }
            $path = $info['name'] . $path;
        });
        return $path;
    }

    public static function traverse($info, $include_root, $cb)
    {
        while ($info) {
            if ($info['type'] != 'direct') {
                break;
            }
            if (! empty($info['direct'])) {
                $config = json_decode($info['direct'], true);
                if (! empty($config['adapter']) && $config['adapter'] != 'inherit') {
                    if ($include_root) {
                        $cb($info);
                    }
                    break;
                }
            }
            $cb($info);
            $info = TikiLib::lib('filegal')->get_file_gallery_info($info['parentId']);
        }
    }
}
