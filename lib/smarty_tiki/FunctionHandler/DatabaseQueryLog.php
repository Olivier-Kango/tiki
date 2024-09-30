<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class DatabaseQueryLog extends Base
{
    public function handle($params, Template $template)
    {
        $smarty = \TikiLib::lib('smarty');
        $queryLogData = \Tiki\Profiling\DatabaseQueryLog::processLog();
        $smarty->assign('queryLogData', $queryLogData);
        $smarty->assign('queryLogLabels', \Tiki\Profiling\DatabaseQueryLog::getLabels());
        $ret = $smarty->fetch('profiling/show_database_query_log.tpl');
        return $ret;
    }
}
