<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Services\TwoFactorAuth;

use Tiki\TwoFactorAuth\Email2FA;
use Tiki\TwoFactorAuth\Exception\TwoFactorAuthException;
use Tiki\TwoFactorAuth\TwoFactorAuthFactory;

class Email2FAController
{
    public function actionGenerateCode($input)
    {
        try {
            $email2FA = new Email2FA();
            $email2FA->generateCode($input->username->text());
            return [
                'success' => true,
                'message' => tr('2FA token generated successfully and sent to your email')
            ];
        } catch (TwoFactorAuthException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function actionIsMFARequired($input)
    {
        return TwoFactorAuthFactory::isMFARequired($input->username->text());
    }
}
