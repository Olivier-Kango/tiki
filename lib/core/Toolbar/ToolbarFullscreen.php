<?php

namespace Tiki\Lib\core\Toolbar;

class ToolbarFullscreen extends ToolbarUtilityItem
{
    public function __construct()
    {
        $this->setLabel(tra('Full-screen edit'))
            ->setIconName('fullscreen')
            ->setWysiwygToken('Maximize')
            ->setType('Fullscreen')
            ->setClass('qt-fullscreen')
        ;
    }

    /**
     * @return string
     */
    public function getOnClick(): string
    {
        return 'toggleFullScreen(\'' . $this->domElementId . '\');return false;';
    }
    public function isAccessible(): bool
    {
        global $jitRequest;

        // doesn't work in modal popups
        if ($jitRequest->modal->int()) {
            return false;
        }

        return parent::isAccessible();
    }
}
