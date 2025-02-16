<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\Exception;
use Smarty\FunctionHandler\Base;
use Smarty\Template;

class Activity extends Base
{
    public function handle($params, Template $template)
    {
        $smarty = \TikiLib::lib('smarty');

        if (isset($params['info'])) {
            $activity = $params['info'];
        } else {
            $lib = \TikiLib::lib('unifiedsearch');
            $docs = $lib->getDocuments('activity', $params['id']);

            $activity = reset($docs);

            if (! $activity) {
                return tr('Not found.');
            }

            $activity = \TikiLib::lib('unifiedsearch')->getRawArray($activity);
        }

        $smarty->assign('activity', $activity);
        $smarty->assign('activity_format', ! empty($params['format']) ? $params['format'] : 'default');
        $templateName = 'activity/' . $activity['event_type'] . '.tpl';

        if (empty($smarty->get_filename($templateName))) {
            $templateName = 'activity/default_event.tpl';
        }
        return $smarty->fetch($templateName);
    }
}
