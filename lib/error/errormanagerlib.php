<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class ErrorManagerLib extends TikiLib
{
    /**
     * Display a generic error message for non admins
     *
     * @param string $message
     * @param string $messageType
     * @return string
     */
    public function genericError(string $message, string $messageType = 'feature'): string
    {
        global $prefs, $tiki_p_admin;
        $errorEnabledNonAdmin = ($prefs['error_generic_non_admins'] ?? 'n') === 'y';

        $logslib = TikiLib::lib('logs');
        $logslib->add_log($messageType, $message);

        if (! $tiki_p_admin && $errorEnabledNonAdmin) {
            $message = tr($prefs['error_generic_message']) ?: tr('There was an issue with your request, please try again later.');
        };

        return $message;
    }
}
