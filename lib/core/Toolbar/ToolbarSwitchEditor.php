<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

class ToolbarSwitchEditor extends ToolbarUtilityItem
{
    private string $onClick = '';

    public function __construct()
    {
        global $prefs;


        if ($prefs['markdown_enabled'] === 'y') {
            $label = tra('Syntax and Editor Settings');
        } else {
            $label = tra('Switch Editor (wiki or WYSIWYG)');
        }

        $this->setLabel(tra($label))
            ->setIconName('cog')
            ->setIcon('img/icons/gear.png')
            ->setWysiwygToken('tikiswitch')
            ->setMarkdownSyntax('tikiswitch')
            ->setMarkdownWysiwyg('tikiswitch')
            ->setType('SwitchEditor')
            ->setClass('qt-switcheditor');
    }

    public function getWikiHtml(): string
    {
        return $this->getPlainHtml();
    }

    public function getMarkdownHtml(): string
    {
        return $this->getPlainHtml();
    }

    /**
     * @return string
     */
    private function getPlainHtml(): string
    {
        $smarty = TikiLib::lib('smarty');
        $servicelib = TikiLib::lib('service');

        $params = ['controller' => 'edit', 'action' => 'editor_settings', 'modal' => 1, 'domId' => $this->domElementId, 'syntax' => $this->getEditorSyntaxType()];

        $icon = \SmartyTiki\FunctionHandler\Icon::render(['name' => $this->iconname], $smarty->getEmptyInternalTemplate());
        $url = $servicelib->getUrl($params);
        $title = tra($this->label);

        return "<a title=\":$title\" class=\"toolbar btn btn-sm px-2 qt-help tips bottom click-modal\" href=\"$url\" data-modal-size=\"modal-md\">$icon</a>";
    }

    public function getWysiwygToken(): string
    {
        return $this->wysiwyg;
    }

    public function getWysiwygJs(): string
    {
        $servicelib = TikiLib::lib('service');
        $syntax = $this->getEditorSyntaxType();
        $params = ['controller' => 'edit', 'action' => 'editor_settings', 'modal' => 1, 'domId' => $this->domElementId, 'type' => 'wysiwyg', 'syntax' => $syntax];
        return '$.openModal({show: true, remote: "' . $servicelib->getUrl($params) . '"});';
    }

    public function getMarkdownWysiwyg(): string
    {
        $this->onClick = $this->getWysiwygJs();

        if (! empty($this->markdown_wysiwyg)) {
            return parent::getMarkdownWysiwyg();
        }
        return '';
    }

    /**
     * Determines whether the switch button is available in the current request.
     *
     * Syntax switching and editor-mode switching are separate capabilities:
     * Markdown syntax switching requires 'markdown_enabled';
     * switching between the wiki and WYSIWYG editors requires both 'feature_wysiwyg' and 'wysiwyg_optional'.
     * Either capability can expose the shared button, but
     * the user must also have 'tiki_p_edit_switch_mode', and the button is
     * hidden during section editing ('hdr' requests).
     *
     * @return bool Whether the user can access the switch button.
     */
    public function isAccessible(): bool
    {
        global $prefs, $tiki_p_edit_switch_mode;

        $canSwitchSyntax = $prefs['markdown_enabled'] === 'y';
        $canSwitchEditorMode = $prefs['feature_wysiwyg'] === 'y' && $prefs['wysiwyg_optional'] === 'y';

        return ($canSwitchSyntax || $canSwitchEditorMode) &&
            ! isset($_REQUEST['hdr']) &&
            $tiki_p_edit_switch_mode === 'y';
    }

    /**
     * @return string
     */
    public function getOnClick(): string
    {
        return $this->onClick; // set by markdown wysiwyg
    }
}
