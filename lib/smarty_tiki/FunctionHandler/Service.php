<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use SmartyTiki\TikiSmartyExtensionInterface;

class Service extends Base implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'service';
    }

    public function handle($params, Template $template)
    {
        $servicelib = \TikiLib::lib('service');

        if (! isset($params['controller'])) {
            return 'missing-controller';
        }

        if (isset($params['_params'])) {
            $params += $params['_params'];
            unset($params['_params']);
        }

        if (isset($params['external']) && $params['external']) {
            \TikiLib::setExternalContext(true);
        }

        $url = $servicelib->getUrl($params);
        return smarty_modifier_escape($url);
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
