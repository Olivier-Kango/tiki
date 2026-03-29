<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use SmartyTiki\TikiSmartyExtensionInterface;

class TrackerItemStatusIcon extends Base implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'tracker_item_status_icon';
    }

    public function handle($params, Template $template)
    {
        global $prefs;

        if (empty($params['item'])) {
            return '';
        }

        $item = $params['item'];

        if (! is_object($item)) {
            $item = \Tracker_Item::fromId($item);
        }

        if (! empty($prefs['tracker_status_in_objectlink'])) {
            $show_status = $prefs['tracker_status_in_objectlink'];
        } else {
            $show_status = 'y';
        }

        if (($show_status == 'y') && $item && $status = $item->getDisplayedStatus()) {
            return smarty_function_icon([
                'name' => 'status-' . $status,
                'iclass' => 'tips',
                'ititle' => ':' . tr($status),
            ], $template);
        }

        return '';
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
