<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function validator_uniqueemail($input, $parameter = '', $message = '')
{
    global $prefs;
    $userlib = TikiLib::lib('user');
    if ($prefs['user_unique_email_validation'] === 'y' && $userlib->get_user_by_email($input)) {
        return tra("Email already in use");
    }
    return true;
}
