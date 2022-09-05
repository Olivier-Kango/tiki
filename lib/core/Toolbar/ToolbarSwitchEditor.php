<?php

namespace Tiki\Lib\core\Toolbar;

class ToolbarSwitchEditor extends ToolbarUtilityItem
{

    public function __construct()
    {
        $this->setLabel(tra('Switch Editor (wiki or WYSIWYG)'))
            ->setIconName('pencil')
            ->setIcon(tra('img/icons/pencil_go.png'))
            ->setWysiwygToken('tikiswitch')
            ->setType('SwitchEditor')
            ->setClass('qt-switcheditor')
            ->addRequiredPreference('feature_wysiwyg');
    }

    public function getWysiwygToken(): string
    {
        global $prefs;
        if (! empty($this->wysiwyg)) {
            if ($prefs['feature_wysiwyg'] == 'y' && $prefs['wysiwyg_optional'] == 'y') {
                $js = "switchEditor('wiki', $('#$this->domElementId').parents('form')[0]);";
                $this->setupCKEditorTool($js, $this->wysiwyg, $this->label, $this->icon);
            }
        }
        return $this->wysiwyg;
    }


    public function isAccessible(): bool
    {
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
        return 'switchEditor(\'wysiwyg\', $(this).parents(\'form\')[0]);';
    }
}
