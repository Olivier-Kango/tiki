<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * Smarty function ticket handler
 * -------------------------------
 * Returns a security token as well as related HTML for including in a form depending on parameters set
 *
 * $params array - Used in most forms with no parameters to include a token as a hidden input in the form
 *                      - Set mode=confirm in order to add the confirmForm hidden input. This used in confirmation forms
 *                          so that $access->checkCsrfForm() knows this is the confirmation post so it will allow the
 *                          action to be performed instead of displaying the confirmation form
 *                      - Set mode=get to return token only with no HTML. Used with links that lead to state-changing
 *                          actions where the confirmSimple() onclick method is used to generate a confirmation form
 *                          with the token
 */
class Ticket extends Base
{
    public function handle($params, Template $template)
    {
        // Redefining the $smarty variable seems to be necessary in some cases (e.g., with ajax services) in order for a
        // ticket that has been set in $access->setTicket() to be retrievable using the $smarty->getTemplateVars() method
        $smarty = \TikiLib::lib('smarty');
        if (empty($smarty->getTemplateVars('ticket'))) {
            \TikiLib::lib('access')->setTicket();
        }
        if (isset($params['mode']) && $params['mode'] === 'get') {
            return urlencode($smarty->getTemplateVars('ticket'));
        } else {
            $ret = '<input type="hidden" class="ticket" name="ticket" value="' . urlencode($smarty->getTemplateVars('ticket'))
            . '" />';
            if (isset($params['mode']) && $params['mode'] === 'confirm') {
                $ret .= '<input type="hidden" name="confirmForm" value="y" />';
            }
            return $ret;
        }
    }
}
