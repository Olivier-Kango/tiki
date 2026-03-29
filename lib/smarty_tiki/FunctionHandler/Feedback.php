<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use SmartyTiki\TikiSmartyExtensionInterface;

class Feedback extends Base implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'feedback';
    }

    public function handle($params, Template $template)
    {
        $smarty = \TikiLib::lib('smarty');
        $result = \Feedback::get();
        if (is_array($result)) {
            $smarty->assign('tikifeedback', $result);
        }
        $ret = $smarty->fetch('feedback/default.tpl');
        return $ret;
    }

    /**
     * Static facade for calling this handler from PHP code without a template.
     */
    public static function render(array $params, ?\Smarty\Template $template = null): string
    {
        if ($template === null) {
            $template = \TikiLib::lib('smarty')->getEmptyInternalTemplate();
        }
        return (new self())->handle($params, $template);
    }
}
