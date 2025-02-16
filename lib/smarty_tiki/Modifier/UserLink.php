<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

/**
 * \brief Smarty modifier to create user links with optional mouseover info
 *
 * - type:     modifier
 * - name:     userlink
 * - purpose:  to return a user link
 *
 * @param string class (optional)
 * @param string idletime (optional)
 * @param string fullname (optional)
 * @param integer max_length (optional)
 * @return string user link
 *
 * Syntax: {$foo|userlink[:"<class>"][:"<idletime>"][:"<fullname>"][:<max_length>]} (optional params in brackets)
 *
 * Example: {$userinfo.login|userlink:'link':::25}
 */
class UserLink
{
    public function handle($other_user, $class = 'userlink', $idletime = 'not_set', $fullname = '', $max_length = 0, $popup = '')
    {
        global $prefs;

        if (empty($other_user)) {
            return "";
        }
        if (is_array($other_user)) {
            if (count($other_user) > 1) {
                $other_user = array_map(
                    function ($username) use ($class, $idletime, $popup) {
                        $username = \TikiLib::lib('user')->distinguish_anonymous_users($username);
                        return smarty_modifier_userlink($username, $class, $idletime, '', 0, $popup);
                    },
                    $other_user
                );

                $last = array_pop($other_user);
                return tr('%0 and %1', implode(', ', $other_user), $last);
            } else {
                $other_user = \TikiLib::lib('user')->distinguish_anonymous_users(reset($other_user));
            }
        } else {
            $other_user = \TikiLib::lib('user')->distinguish_anonymous_users($other_user);
        }
        if (! $fullname) {
            $fullname = \TikiLib::lib('user')->clean_user($other_user);
        }
        if ($max_length) {
            $fullname = smarty_modifier_truncate($fullname, $max_length, '...', true);
        }

        if (empty($popup) && $prefs['feature_community_mouseover'] == 'n') {
            $popup = 'n';
        } else {
            $popup = 'y';
        }

        return \TikiLib::lib('user')->build_userinfo_tag($other_user, htmlspecialchars($fullname, ENT_QUOTES), $class, $popup);
    }
}
