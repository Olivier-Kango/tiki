<?php

namespace Tiki\Lib\core\Toolbar;

class ToolbarInline extends ToolbarItem
{
    protected string $syntax;

    public static function fromName($tagName): ?ToolbarItem
    {
        switch ($tagName) {
            case 'bold':
                $label = tra('Bold');
                $icon = tra('img/icons/text_bold.png');
                $iconname = 'bold';
                $wysiwyg = 'Bold';
                $syntax = '__text__';
                break;
            case 'italic':
                $label = tra('Italic');
                $icon = tra('img/icons/text_italic.png');
                $iconname = 'italic';
                $wysiwyg = 'Italic';
                $syntax = "''text''";
                break;
            case 'underline':
                $label = tra('Underline');
                $icon = tra('img/icons/text_underline.png');
                $iconname = 'underline';
                $wysiwyg = 'Underline';
                $syntax = "===text===";
                break;
            case 'strike':
                $label = tra('Strikethrough');
                $icon = tra('img/icons/text_strikethrough.png');
                $iconname = 'strikethrough';
                $wysiwyg = 'Strike';
                $syntax = '--text--';
                break;
            case 'code':
                $label = tra('Code');
                $icon = tra('img/icons/page_white_code.png');
                $iconname = 'code';
                $wysiwyg = 'Code';
                $syntax = '-+text+-';
                break;
            case 'nonparsed':
                $label = tra('Non-parsed (wiki syntax does not apply)');
                $icon = tra('img/icons/noparse.png');
                $iconname = 'ban';
                $wysiwyg = '';
                $syntax = '~np~text~/np~';
                break;
            default:
                return null;
        }

        $tag = new self();
        $tag->setLabel($label)
            ->setWysiwygToken($wysiwyg)
            ->setIconName(! empty($iconname) ? $iconname : 'help')
            ->setIcon(! empty($icon) ? $icon : 'img/icons/shading.png')
            ->setSyntax($syntax)
            ->setType('Inline')
            ->setClass('qt-inline');

        return $tag;
    }

    public function getSyntax(): string
    {
        return $this->syntax;
    }

    public function setSyntax(string $syntax): ToolbarItem
    {
        $this->syntax = $syntax;

        return $this;
    }

    public function getWikiHtml(): string
    {
        if ($this->syntax == '~np~text~/np~') { // closing ~/np~ tag breaks toolbar when inside nested plugins
            return $this->getSelfLink(
                'insertAt(\'' . $this->domElementId . '\', \'~np~text~\'+\'/np~\');',
                htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
                $this->getClass()
            );
        } else {
            return $this->getSelfLink(
                $this->getOnClick(),
                htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
                $this->getClass()
            );
        }
    }

    /**
     * @return string
     */
    public function getOnClick(): string
    {
        return 'insertAt(\'' . $this->domElementId . '\', \'' .
            addslashes(
                htmlentities($this->syntax, ENT_COMPAT, 'UTF-8')
            ) . '\');';
    }
}
