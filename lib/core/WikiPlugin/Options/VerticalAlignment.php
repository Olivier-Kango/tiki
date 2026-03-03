<?php

namespace Tiki\WikiPlugin\Options;

enum VerticalAlignment: string implements PluginOptionsInterface
{
    use HasEnumLabelsAndOptionsTrait;

    case Top = 'top';
    case Center = 'center';
    case Bottom = 'bottom';

    public static function getLabel(PluginOptionsInterface $value, string $labelSet): string
    {
        return match ($value) {
            self::Top    => tr('Top'),
            self::Center => tr('Center'),
            self::Bottom => tr('Bottom'),
        };
    }
}
