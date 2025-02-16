<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/*
These 4 lines of code are a presentation of Louis-Philippe Huberdeau
For Tiki CMS/Groupware
Released under the same licence as the rest of the project

This page is only meant to be called from the browser's XMLHttpRequest
classes. All it does is grab the parameters and store them in the
session.

The parameters in the session will be stored under
$_SESSION['tiki_cookie_jar'][ $parameter_name ]
*/

require_once('tiki-setup.php');

if (isset($_GET)) {
    foreach ($_GET as $key => $value) {
        $_SESSION['tiki_cookie_jar'][$key] = $value;
    }
}
