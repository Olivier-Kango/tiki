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
        $menu_option_id = $input->menu_option_id->text();
        $res = $tikilib->query("UPDATE tiki_menu_options SET icon = ? WHERE optionId = ?", [$icon_name, $menu_option_id]);
        return $res ? ['element' => $menu_option_id] : ['element' => null];
    }
}
