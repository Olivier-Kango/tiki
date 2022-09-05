<?php

namespace Tiki\Lib\core\Toolbar;

class ToolbarBlock extends ToolbarInline // Will change in the future
{
    public static function fromName($tagName): ?ToolbarItem
    {
        global $prefs;

        $label = '';
        $wysiwyg = '';
        $syntax = '';

        switch ($tagName) {
            case 'center':
                $label = tra('Align Center');
                $iconname = 'align-center';
                $wysiwyg = 'JustifyCenter';
                if ($prefs['feature_use_three_colon_centertag'] == 'y') {
                    $syntax = ":::text:::";
                } else {
                    $syntax = "::text::";
                }
                break;
            case 'rule':
                $label = tra('Horizontal Bar');
                $iconname = 'horizontal-rule';
                $wysiwyg = 'HorizontalRule';
                $syntax = '---';
                break;
            case 'pagebreak':
                $label = tra('Page Break');
                $iconname = 'page-break';
                $wysiwyg = 'PageBreak';
                $syntax = '...page...';
                break;
            case 'box':
                $label = tra('Box');
                $iconname = 'box';
                $wysiwyg = 'Box';
                $syntax = '^text^';
                break;
            case 'email':
                $label = tra('Email');
                $iconname = 'envelope';
                $syntax = '[mailto:email@example.com|text]';
                break;
            case 'h1':
            case 'h2':
            case 'h3':
                $label = tra('Heading') . ' ' . $tagName[1];
                $iconname = $tagName;
                $syntax = str_repeat('!', $tagName[1]) . ' text';
                break;
            case 'titlebar':
                $label = tra('Title bar');
                $iconname = 'title';
                $syntax = '-=text=-';
                break;
            case 'toc':
                $label = tra('Table of contents');
                $iconname = 'book';
                $wysiwyg = 'TOC';
                $syntax = '{maketoc}';
                break;
            default:
                return null;
        }

        $tag = new self();
        $tag->setLabel($label)
            ->setWysiwygToken($wysiwyg)
            ->setIconName(! empty($iconname) ? $iconname : 'help')
            ->setSyntax($syntax)
            ->setType('Block')
            ->setClass('qt-block');

        return $tag;
    }

    /**
     * @return string
     */
    public function getOnClick(): string
    {
        return 'insertAt(\'' . $this->domElementId . '\', \'' .
            addslashes(
                htmlentities($this->syntax, ENT_COMPAT, 'UTF-8')
            ) . '\', true);';
    }
}
