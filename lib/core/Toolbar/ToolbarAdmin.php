<?php

namespace Tiki\Lib\core\Toolbar;

class ToolbarAdmin extends ToolbarItem
{

    public function __construct()
    {
        $this->setLabel(tra('Admin Toolbars'))
            ->setIconName('wrench')
            ->setIcon(tra('img/icons/wrench.png'))
            ->setWysiwygToken('admintoolbar')
            ->setType('admintoolbar')
            ->setClass('qt-admintoolbar');
    }

    public function getWysiwygToken(): string
    {
        global $prefs;
        if (! empty($this->wysiwyg)) {
            $name = $this->wysiwyg;   // temp

            if ($prefs['feature_wysiwyg'] == 'y') {
                $js = "admintoolbar();";
                $this->setupCKEditorTool($js, $name, $this->label, $this->icon);
            }
        }
        return $this->wysiwyg;
    }

    /**
     * @return string
     */
    public function getOnClick(): string
    {
        return 'admintoolbar();';
    }
}
