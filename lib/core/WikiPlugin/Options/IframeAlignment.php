<?php

namespace Tiki\WikiPlugin\Options;

use Tiki\WikiPlugin\Options\PluginOptionsInterface;

enum IframeAlignment: string implements PluginOptionsInterface
{
    use HasEnumLabelsAndOptionsTrait;

    case Left = 'left';
    case Right = 'right';
    case Middle = 'middle';
    case Top = 'top';
    case Bottom = 'bottom';

    public static function getLabel(PluginOptionsInterface $value, string $labelSet): string
    {
        return match ($value) {
            self::Left => tr('Left'),
            self::Right => tr('Right'),
            self::Middle => tr('Middle'),
            self::Top => tr('Top'),
            self::Bottom => tr('Bottom'),
        };
    }
}
