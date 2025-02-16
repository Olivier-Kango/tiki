<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * Variable arguments to be sent as filters for the object list. Filters match the unified search
 * field filters.
 *
 * Reserved parameters:
 *  - name for the field name
 *  - galleryId
 *  - value for the current value (fileId, comma-separated for multiple)
 *  - type for the mime type filter (image/*)
 *  - limit for the maximum amount of files (defaults to 1)
 *
 * The component will build a drop list for the object selector if the results fit in a reasonable amount
 * of space or will use autocomplete on the object title otherwise.
 */
class FileSelector extends Base
{
    public function handle($params, Template $template)
    {
        static $uniqid = 0;

        $arguments = [
            'name' => null,
            'value' => null,
            'limit' => 1,
            'type' => null,
            'galleryId' => 0,
        ];

        $input = new \JitFilter(array_merge($arguments, $params));
        $input->replaceFilter('value', 'int');
        $smarty = \TikiLib::lib('smarty');

        $smarty->assign('file_selector', [
            'name' => $input->name->text(),
            'value' => array_filter($input->asArray('value', ',')),
            'limit' => $input->limit->digits() ?: 1,
            'type' => $input->type->text(),
            'galleryId' => $input->galleryId->int(),
        ]);
        return $smarty->fetch('file_selector.tpl');
    }
}
