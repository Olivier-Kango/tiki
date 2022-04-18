<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/*
 * If installed, this modifier will use Kint (from https://github.com/kint-php/kint/)
 *
 *   You need to enable dev mode for composer by running `php console.php help dev:configure`
 *   and then setup.sh
 *
 * Example usage:
 *
 *     {$smarty.request|d}
 */

function smarty_modifier_d($var, $modifier = '')  {

    if (is_callable('Kint::dump')) {

        // add this function as an alias of Kint::dump
        Kint::$aliases[] = 'smarty_modifier_d';

        switch ($modifier) {
            case '!':                   // Expand all data in this dump automatically
                !Kint::dump($var);
                break;
            case '+':                   // Disable the depth limit in this dump
                +Kint::dump($var);
                break;
            case '-':                   // Clear buffered output and flush after dump
                -Kint::dump($var);
                break;
            case '@':                   // Return the output of this dump instead of echoing it
                @Kint::dump($var);
                break;
            case '~':                   // Use the text renderer for this dump
                ~Kint::dump($var);
                break;
            default:
                Kint::dump($var);
        }
    }
}