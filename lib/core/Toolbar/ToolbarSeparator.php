<?php

namespace Tiki\Lib\core\Toolbar;

class ToolbarSeparator extends ToolbarUtilityItem
{
    public function __construct()
    {
        $this->setWysiwygToken('-')
            ->setIcon('img/separator.gif')
            ->setType('Separator');
    }

    public function getWikiHtml(): string
    {
        return '|';
    }

    protected function getOnClick(): string
    {
        return '';
    }
}