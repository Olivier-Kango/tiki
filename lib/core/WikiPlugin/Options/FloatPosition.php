<?php

namespace Tiki\WikiPlugin\Options;

use Tiki\WikiPlugin\Options\PluginOptionsInterface;

enum FloatPosition: string implements PluginOptionsInterface
{
    use HasEnumLabelsAndOptionsTrait;

    case None = 'none';
    case Left = 'left';
    case Right = 'right';

    public static function getLabel(PluginOptionsInterface $value, string $labelSet): string
    {
        return match ($value) {
            self::Left => tr('Left'),
            self::Right => tr('Right'),
            self::None => tr('None'),
        };
    }
}
