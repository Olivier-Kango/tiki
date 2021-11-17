<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
/*
 * Created on Jan 30, 2009
 *
 * Parent class of all test cases. For some reason PHPUnit doesn't deal
 * well with globals, so $backupGlobals should be set to false.
 * Use this class to set other PHPUnit variables, as needed.
 *
 */

//require_once (version_compare(PHPUnit_Runner_Version::id(), '3.5.0', '>=')) ? 'PHPUnit/Autoload.php' : 'PHPUnit/Framework.php';

use PHPUnit\Framework\TestCase;

abstract class TikiTestCase extends TestCase
{
    protected $backupGlobals = false;

    protected function ensureDefaultGalleryExists() {
        $filegallib = TikiLib::lib('filegal');
        $info = $filegallib->get_file_gallery_info(1);
        if (! $info) {
            if (! isset($GLOBALS['user'])) {
                $GLOBALS['user'] = '';
            }
            $galleryId = $filegallib->replace_file_gallery(
                [
                    'name' => 'Default Gallery',
                    'description' => 'Default Gallery',
                ]
            );
            $filegallib->query("UPDATE tiki_file_galleries SET galleryId = 1 WHERE galleryId     = ?", [$galleryId]);
        }
    }
}
