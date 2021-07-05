<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function validator_smarty($input, $parameter = '', $message = '')
{
    /** @var Smarty_Tiki $smarty */
    $smarty = \TikiLib::lib('smarty');

    try {
        ob_start();
        if ($parameter === 'y') {
            $input = TikiLib::lib('parser')->parse_data($input);
        }
        $smarty->display('eval:' . $input);
        ob_end_clean();
        return true;
    } catch (Exception $e) {
        // error is always on line 1 in a string eval, so simplify the error message a little
        return preg_replace('/Syntax error in template .* on line \d+/', 'Syntax error in: ', $e->getMessage());
    }
}
