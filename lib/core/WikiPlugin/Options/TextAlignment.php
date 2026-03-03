<?php

namespace Tiki\WikiPlugin\Options;

enum TextAlignment: string implements PluginOptionsInterface
{
    use HasEnumLabelsAndOptionsTrait;

    case Left = 'left';
    case Right = 'right';
    case Center = 'center';
    case Justify = 'justify';

    public static function getLabel(PluginOptionsInterface $value, string $labelSet): string
    {
        return match ($value) {
            self::Left => tr('Left'),
            self::Right => tr('Right'),
            self::Center => tr('Center'),
            self::Justify => tr('Justify'),
        };
    }
}
