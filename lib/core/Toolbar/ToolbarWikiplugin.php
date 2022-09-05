<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

class ToolbarWikiplugin extends ToolbarItem
{
    private string $pluginName;

    public static function fromName($name)
    {
        $parserlib = TikiLib::lib('parser');

        if (substr($name, 0, 11) == 'wikiplugin_') {
            $name = substr($name, 11);
            if ($info = $parserlib->plugin_info($name)) {
                $tag = new self();
                $tag->setLabel(str_ireplace('wikiplugin_', '', $info['name']))
                    ->setWysiwygToken(str_replace(' ', '_', $info['name']))
                    ->setPluginName($name)
                    ->setType('Wikiplugin')
                    ->setClass('qt-plugin');

                if (! empty($info['iconname'])) {
                    $tag->setIconName($info['iconname']);
                } elseif (! empty($info['icon'])) {
                    $tag->setIcon($info['icon']);
                } else {
                    $tag->setIcon('img/icons/plugin.png');
                }

                TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-toolbars.js');

                return $tag;
            }
        }
    }

    public function setPluginName(string $name): ToolbarItem
    {
        $this->pluginName = $name;

        return $this;
    }

    public function getPluginName(): string
    {
        return $this->pluginName;
    }

    public function isAccessible(): bool
    {
        global $tikilib;
        $parserlib = TikiLib::lib('parser');
        $dummy_output = '';
        return parent::isAccessible() && $parserlib->plugin_enabled($this->pluginName, $dummy_output);
    }

    private static function getToken($name)
    {
        switch ($name) {
            case 'flash':
                return 'Flash';
        }
    }

    public function getWysiwygToken($add_js = true): string
    {
        if (! empty($this->wysiwyg) && $add_js) {
            $js = "popupPluginForm(editor.name,'{$this->pluginName}');";
            //CKEditor needs image icons so get legacy plugin icons for the toolbar
            $iconpath = 'img/icons/plugin.png';
            if (! $this->icon && ! empty($this->iconname)) {
                $iconsetlib = TikiLib::lib('iconset');
                $legacy = $iconsetlib->loadFile('themes/base_files/iconsets/legacy.php');
                if (array_key_exists($this->iconname, $legacy['icons'])) {
                    $iconinfo = $legacy['icons'][$this->iconname];
                } elseif (in_array($this->iconname, $legacy['defaults'])) {
                    $iconinfo['id'] = $this->iconname;
                }
                if (isset($iconinfo)) {
                    $prepend = $iconinfo['prepend'] ?? 'img/icons/';
                    $append = $iconinfo['append'] ?? '.png';
                    $iconpath = $prepend . $iconinfo['id'] . $append;
                }
            }
            $this->setupCKEditorTool($js, $this->wysiwyg, $this->label, $iconpath);
        }
        return $this->wysiwyg;
    }

    public function getWysiwygWikiToken($add_js = true): string // wysiwyg_htmltowiki
    {
        switch ($this->pluginName) {
            case 'img':
                $this->wysiwyg = 'wikiplugin_img';  // don't use ckeditor's html image dialog
                break;
            default:
        }

        return $this->getWysiwygToken($add_js);
    }

    /**
     * @return string
     */
    public function getOnClick(): string
    {
        return 'popupPluginForm(\'' . $this->domElementId . '\',\'' . $this->pluginName . '\')';
    }
}