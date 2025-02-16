<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class Breadcrumbs extends Base
{
    public function handle($params, Template $template)
    {
        global $prefs;
        extract($params);

        if (empty($crumbs)) {
            trigger_error("assign: missing 'crumbs' parameter");
            return;
        }
        if (empty($loc)) {
            trigger_error("assign: missing 'loc' parameter");
            return;
        }
        if ($type === 'pagetitle' && $prefs['site_title_breadcrumb'] === 'y') {
            $type = 'desc';
        }
        $showLinks = empty($params['showLinks']) || $params['showLinks'] == 'y';
        $text_to_display = '';
        switch ($type) {
            case 'invertfull':
                $text_to_display = breadcrumb_buildHeadTitle(array_reverse($crumbs));
                break;
            case 'fulltrail':
                $text_to_display = breadcrumb_buildHeadTitle($crumbs);
                break;
            case 'pagetitle':
                $text_to_display = breadcrumb_getTitle($crumbs, $loc);
                break;
            case 'desc':
                $text_to_display = breadcrumb_getDescription($crumbs, $loc);
                break;
            case 'trail':
            default:
                $text_to_display = breadcrumb_buildTrail($crumbs, $loc, $showLinks);
                break;
        }

        return $text_to_display;
    }
}
