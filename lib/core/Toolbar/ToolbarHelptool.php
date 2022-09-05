<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

class ToolbarHelptool extends ToolbarUtilityItem
{
    public function __construct()
    {
        $this->setLabel(tra('Wiki Help'))
            ->setIcon('img/icons/help.png')
            ->setType('Helptool')
            ->setClass('qt-help');
    }

    public function getWikiHtml(): string
    {
        $smarty = TikiLib::lib('smarty');
        $servicelib = TikiLib::lib('service');

        $params = ['controller' => 'edit', 'action' => 'help', 'modal' => 1];
        $params['wiki'] = 1;
        $params['plugins'] = 1;
        $params['areaId'] = $this->domElementId;

        if ($GLOBALS['section'] == 'sheet') {
            $params['sheet'] = 1;
        }

        $smarty->loadPlugin('smarty_function_icon');
        $icon = smarty_function_icon(['name' => 'help'], $smarty->getEmptyInternalTemplate());
        $url = $servicelib->getUrl($params);
        $help = tra('Help');

        return "<a title=\":$help\" class=\"toolbar btn btn-sm px-2 qt-help tips bottom\" href=\"$url\" data-bs-toggle=\"modal\" data-bs-target=\"#bootstrap-modal\">$icon</a>";
    }

    public function getWysiwygToken(): string
    {
        global $section;

        $servicelib = TikiLib::lib('service');

        $params = ['controller' => 'edit', 'action' => 'help', 'modal' => 1];
        $params['wysiwyg'] = 1;
        $params['plugins'] = 1;

        if ($section == 'sheet') {
            $params['sheet'] = 1;
        }

        // multiple ckeditors share the same toolbar commands, so area_id (editor.name) must be added when clicked
        $params['areaId'] = ''; // this must be last param

        $this->setLabel(tra('WYSIWYG Help'));
        $this->setIconName('help');
        $name = 'tikihelp';

        $js = '$.openModal({show: true, remote: "' . $servicelib->getUrl($params) . '" + editor.name});';

        $this->setupCKEditorTool($js, $name, $this->label, $this->icon);

        return $name;
    }

    protected function getOnClick(): string
    {
        // not needed as bootstrap does the onclick
        return '';
    }
}