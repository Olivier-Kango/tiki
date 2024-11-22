<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\TwoFactorAuth;

use Tiki\TwoFactorAuth\Exception\TwoFactorAuthException;

class TwoFactorAuthFactory
{
    public static function getTwoFactorAuth()
    {
        global $prefs;

        return self::getTwoFactorAuthByType($prefs['twoFactorAuthType'] ?? 'google2FA');
    }

    public static function getTwoFactorAuthByType($type)
    {
        $authType = ucfirst($type);
        $class = "\\Tiki\\TwoFactorAuth\\$authType";

        if (! class_exists($class)) {
            $errMsg = tr('Two factor auth type not found: ' . $authType . ', Supported types are: google2FA, email2FA');
            throw new TwoFactorAuthException($errMsg);
        }

        $twoFactorAuth = new $class();

        return $twoFactorAuth;
    }

    public static function isMFARequired($user)
    {
        global $prefs, $userlib;

        $mfaIntervalDaysPrefs = intval($prefs['twoFactorAuthIntervalDays']);
        $requireMfa = false;

        if ($prefs['twoFactorAuth'] == 'y') {
            $userInfo = $userlib->get_user_info($user);
            $lastMfaDateDb = intval($userInfo['last_mfa_date']);
            if ($mfaIntervalDaysPrefs > 0) {
                if (empty($lastMfaDateDb) || (time() - $lastMfaDateDb) > ($mfaIntervalDaysPrefs * 86400)) {
                    $requireMfa = true;
                }
            } else {
                $requireMfa = true;
            }
        }

        return $requireMfa;
    }
}
