<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class Help extends Base
{
    public function handle($params, Template $template)
    {
        extract($params);
        // Param = zone
        if (empty($url) && empty($desc) && empty($crumb)) {
            trigger_error("assign: missing parameter: help (url desc)|crumb");
            return;
        }
        print help_doclink($params);
    }
}
