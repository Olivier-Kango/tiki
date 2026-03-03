<?php

namespace Tiki\WikiPlugin\Options;

enum HorizontalAlignment: string implements PluginOptionsInterface
{
    use HasEnumLabelsAndOptionsTrait;

    case Left = 'left';
    case Center = 'center';
    case Right = 'right';


    public static function getLabel(PluginOptionsInterface $value, string $labelSet): string
    {
        return match ($value) {
            self::Left => tr('Left'),
            self::Right => tr('Right'),
            self::Center => tr('Center'),
        };
    }
}
