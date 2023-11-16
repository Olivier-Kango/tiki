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

/*
Important conventions:

- Do NOT compose paths in this file (ex:  TEMP_CACHE_PATH = TEMP_PATH . 'temp'; It must be TEMP_CACHE_PATH = 'temp/cache').  This is so the strings are findable if someone tries to fine what a file found in the filesystem does.

- Directories path must NOT end with a /
*/

const DB_PATH = 'db';
const FILES_PATH = 'files';
const IMG_WIKI_PATH = 'img/wiki';
const IMG_WIKI_UP_PATH = 'img/wiki_up';
const IMG_TRACKERS_PATH = 'img/trackers';
const NODE_MODULES_PATHNAME = 'node_modules';
const MODS_PATH = 'mods';
/** Path to store temporary files.  Use one of the more specific path if possible */
const TEMP_PATH = 'temp';
/** Temporary files that must be servable by the webserver */
const TEMP_HTTP_PUBLIC_PATH = 'temp/public';
const TEMP_CACHE_PATH = 'temp/cache';
const SMARTY_COMPILED_TEMPLATES_PATH = 'temp/templates_c';
const SMARTY_TEMPLATES_PATH = 'templates';
const THEMES_PATH = 'themes';
/** This it for the old tiki_tests system which may not be functionnal - benoitg - 2023-11-16 */
const TIKI_TESTS_PATH = 'tiki_tests/tests';
const WHELP_PATH = 'whelp';
/** This is where custom themes, php files, etc. are stored */
const USER_CUSTOM_PATH = '_custom';
/** If present, this php file will be included at the end of tiki-setup.php.  A last resort for custom php code */
const USER_CUSTOM_PHP_FILE = '_custom/lib/setup/custom.php';
const TIKI_VENDOR_PATH = 'vendor'; //VENDOR_PATH caused a problem with CYPHT
const TIKI_VENDOR_BUNDLED_PATH = 'vendor_bundled';
const TIKI_VENDOR_CUSTOM_PATH = 'vendor_custom';


const HTTP_PUBLIC_PATH = 'public';
/* Javascript assets servable over http, including css generated from js files. */
const JS_ASSETS_PATH = 'public/generated/js';
/** This is the path for JS vendor files that would normall be served by a content delivery network.  We both bundle these files in the tarball, and (usually) serve them locally. */
const NODE_PUBLIC_DIST_PATH = 'public/generated/js/vendor_dist';
