<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

class Username implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'username';
    }

    public function handle($user, $login_fallback = true, $check_user_show_realnames = true, $html_encoding = true)
    {
        $userlib = \TikiLib::lib('user');

        $return = $userlib->clean_user($user, ! $check_user_show_realnames, $login_fallback);

        if ($html_encoding) {
            $return = htmlspecialchars($return);
        }
        return $return;
    }

    /**
     * Static facade for calling this modifier from PHP code.
     */
    public static function apply($user, $login_fallback = true, $check_user_show_realnames = true, $html_encoding = true)
    {
        return (new self())->handle($user, $login_fallback, $check_user_show_realnames, $html_encoding);
    }
}
