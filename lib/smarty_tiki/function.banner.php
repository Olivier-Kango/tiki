<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function smarty_function_banner($params, $smarty)
{
    $bannerlib = TikiLib::lib('banner');
    $default = ['zone' => '', 'target' => '', 'id' => ''];
    $params = array_merge($default, $params);

    extract($params);

    if (empty($zone) && empty($id)) {
        trigger_error("assign: missing 'zone' parameter");
        return;
    }
    $banner = $bannerlib->select_banner($zone, $target, $id);

    print($banner);
}
