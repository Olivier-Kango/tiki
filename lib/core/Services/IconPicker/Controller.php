<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_IconPicker_Controller
{
    public $tikilib;
    public function action_change_menu_icon($input)
    {
        $tikilib = TikiLib::lib('db');
        $icon_name = $input->icon_name->text();
        $unparsed_url = $input->unparsed_url->text();
        $url = $unparsed_url;
        if (preg_match("/^tiki-index\.php\?page=\S{1,}/", $unparsed_url)) {//for custom pages, construct the menu link as how it is stored in the DB
            $url = str_replace(['tiki-index.php?page=','-'], ['((',' '], $url);
            $url .= "))";
        }
        $res = $tikilib->query("UPDATE tiki_menu_options SET icon = ? WHERE url = ?", [$icon_name,$url]);
        return $res ? ['element' => $unparsed_url] : ['element' => null];
    }
}
