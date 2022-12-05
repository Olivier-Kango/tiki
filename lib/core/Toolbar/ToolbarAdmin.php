<?php

namespace Tiki\Lib\core\Toolbar;

class ToolbarAdmin extends ToolbarUtilityItem
{
    public function __construct()
    {
        $this->setLabel(tra('Admin Toolbars'))
            ->setIconName('wrench')
            ->setIcon(tra('img/icons/wrench.png'))
            ->setWysiwygToken('admintoolbar')
            ->setMarkdownSyntax('admintoolbar')
            ->setMarkdownWysiwyg('admintoolbar')
            ->setType('admintoolbar')
            ->setClass('qt-admintoolbar');
    }

    public function getWysiwygToken(): string
    {
        global $prefs;
        if (! empty($this->wysiwyg)) {
            if ($prefs['feature_wysiwyg'] == 'y') {
                $this->setupCKEditorTool($this->getOnClick());
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
