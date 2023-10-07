<?php

/**
 * This checks that composer was installed and otherwise displays a web-friendly error page
 *
 * @package Tiki
 * @copyright (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 * @licence Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 */

// this script may only be included - so its better to die if called directly.
// Don't call tiki-setup.php because it does the same test on composer's
// installation and displays a web-ugly error message // which only looks nice in
// command line mode
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

const CUSTOM_PATH = '_custom';
const DB_PATH = 'db';
const FILES_PATH = 'files';
const IMG_WIKI_PATH = 'img/wiki';
const IMG_WIKI_UP_PATH = 'img/wiki_up';
const IMG_TRACKERS_PATH = 'img/trackers';
const NODE_MODULES_PATH = 'node_modules';
const MODS_PATH = 'mods';
const TEMP_PATH = 'temp';
const TEMP_CACHE_PATH = 'temp/cache';
const TEMP_PUBLIC_PATH = 'temp/public';
const TEMP_TEMPLATES_C_PATH = 'temp/templates_c';
const TEMPLATES_PATH = 'templates';
const THEMES_PATH = 'themes';
const TIKI_TESTS_TESTS_PATH = 'tiki_tests/tests';
const WHELP_PATH = 'whelp';
const TIKI_VENDOR_PATH = 'vendor'; //VENDOR_PATH caused a problem with CYPHT
const TIKI_VENDOR_BUNDLED_PATH = 'vendor_bundled';
const TIKI_VENDOR_CUSTOM_PATH = 'vendor_custom';


/* Javascript  */
const JS_ASSETS_PATH = 'public/generated/js';
const CSS_ASSETS_PATH = 'public/generated/js/';
/** This is the path for JS vendor files that would normall be served by a content delivery network.  We both bundle these files in the tarball, and (usually) serve them locally. */
const NODE_PUBLIC_DIST_PATH = 'public/generated/js/vendor_dist';
