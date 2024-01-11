<?php

namespace Tiki\Lib\core\Toolbar;

class ToolbarSwitchEditor extends ToolbarUtilityItem
{
    /**
     * @var string
     */
    public $onClick;
    public function __construct()
    {
        global $prefs;

        if ($prefs['markdown_enabled'] === 'y') {
            $iconname = 'cog';
            $iconPath = 'img/icons/gear.png';
            $label = 'Syntax and Editor Settings';
        } else {
            $iconname = 'pencil';
            $iconPath = 'img/icons/pencil_go.png';
            $label = 'Switch Editor (wiki or WYSIWYG)';
        }

        $this->setLabel(tra($label))
            ->setIconName($iconname)
            ->setIcon(tra($iconPath))
            ->setWysiwygToken('tikiswitch')
            ->setMarkdownSyntax('tikiswitch')
            ->setMarkdownWysiwyg('tikiswitch')
            ->setType('SwitchEditor')
            ->setClass('qt-switcheditor')
            ->addRequiredPreference('feature_wysiwyg');
    }

    public function getWysiwygToken(): string
    {
        global $prefs;
        if (! empty($this->wysiwyg)) {
            if ($prefs['feature_wysiwyg'] == 'y' && $prefs['wysiwyg_optional'] == 'y') {
                if ($prefs['markdown_enabled'] === 'y') {
                    $js = 'editorSettings(\'' . $this->domElementId . '\');';
                } else {
                    $js = "switchEditor('wiki', $('#$this->domElementId').parents('form'));";
                }
                $this->setupCKEditorTool($js);
            }
        }
        return $this->wysiwyg;
    }

    public function getMarkdownWysiwyg(): string
    {
        global $prefs;

        $this->onClick = 'editorSettings("' . $this->domElementId . '");';

        if (! empty($this->markdown_wysiwyg)) {
            if ($prefs['feature_wysiwyg'] == 'y' && $prefs['wysiwyg_optional'] == 'y') {
                return parent::getMarkdownWysiwyg();
            }
        }
        return '';
    }

    public function isAccessible(): bool
    {
        // TODO make object specific check, but we don't know where this toolbar is down here...
        global $tiki_p_edit_switch_mode;

        return parent::isAccessible() &&
            ! isset($_REQUEST['hdr']) &&        // or in section edit
            $tiki_p_edit_switch_mode === 'y';   // or no perm (new in 7.1)
    }

    /**
     * @return string
     */
    public function getOnClick(): string
    {
        global $prefs;

        if ($prefs['markdown_enabled'] === 'y') {
            return 'editorSettings(\'' . $this->domElementId . '\');';
        } else {
            return  'switchEditor(\'wysiwyg\', $(this).parents(\'form\'));';
        }
    }
}
