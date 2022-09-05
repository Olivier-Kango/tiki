<?php

namespace Tiki\Lib\core\Toolbar;

class ToolbarLineBased extends ToolbarInline // Will change in the future
{
    public static function fromName($tagName): ?ToolbarItem
    {
        switch ($tagName) {
            case 'list':
                $label = tra('Bullet List');
                $iconname = 'list';
                $wysiwyg = 'BulletedList';
                $syntax = '* text';
                break;
            case 'numlist':
                $label = tra('Numbered List');
                $iconname = 'list-numbered';
                $wysiwyg = 'NumberedList';
                $syntax = '# text';
                break;
            case 'indent':
            default:
                return null;
        }

        $tag = new self();
        $tag->setLabel($label)
            ->setWysiwygToken($wysiwyg)
            ->setIconName(! empty($iconname) ? $iconname : 'help')
            ->setSyntax($syntax)
            ->setType('LineBased')
            ->setClass('qt-line');

        return $tag;
    }

    public function getWikiHtml(): string
    {
        return $this->getSelfLink(
            'insertAt(\'' . $this->domElementId . '\', \'' . addslashes(
                htmlentities($this->syntax, ENT_COMPAT, 'UTF-8')
            ) . '\', true, true);',
            htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
            $this->getClass()
        );
    }
}
