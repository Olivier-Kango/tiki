<?php

namespace Tiki\Lib\core\Toolbar;

abstract class ToolbarUtilityItem extends ToolbarItem
{
    abstract protected function getOnClick(): string;
}
