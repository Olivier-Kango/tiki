<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use TikiLib;

/**
 * @param $params
 *               - fieldName: name attribute for the input element
 *               - data: array of items to display in the list. Format: [value => label, ...]
 *               - defaultSelected: array of items to be selected by default. Format: [value, ...]
 *               - sourceListTitle: title of the source list
 *               - targetListTitle: title of the target list
 *               - filterable: boolean. Whether or not to enable filtering
 *               - filterPlaceholder: placeholder text for the filter input
 *               - ordering: boolean. Whether or not to enable ordering of items via drag and drop
 *
 * @param $smarty
 *
 * @return string
 * @throws Exception
 */
class JsTransferList extends Base
{
    public function handle($params, Template $template)
    {
        $defaultParams = [
            'filterPlaceholder' => tr('Enter keyword'),
            'sourceListTitle' => tr('List'),
            'targetListTitle' => tr('Selected'),
            'defaultSelected' => [],
        ];
        $params = array_merge($defaultParams, $params);
        $params['defaultSelected'] = array_map('strval', $params['defaultSelected']);
        $language = TikiLib::lib('tiki')->get_language();

        TikiLib::lib('header')->add_js_module("import '@vue-widgets/element-plus-ui';");

        return "
        <element-plus-ui component='Transfer' language=" . json_encode($language) . " data='" . json_encode($params["data"]) . "' field-name=" . json_encode($params["fieldName"]) . " filterable=" . json_encode((bool) $params["filterable"]) . " default-value='" . json_encode($params["defaultSelected"]) . "' source-list-title=" . json_encode(tr($params["sourceListTitle"])) . " target-list-title=" . json_encode(tr($params["targetListTitle"])) . " filter-placeholder=" . json_encode(tr($params["filterPlaceholder"])) . " ordering=" . json_encode((bool) $params["ordering"]) . " >
        </element-plus-ui>";
    }
}
