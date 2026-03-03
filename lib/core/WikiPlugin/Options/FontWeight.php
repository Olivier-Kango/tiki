<?php

namespace Tiki\WikiPlugin\Options;

enum FontWeight: string implements PluginOptionsInterface
{
    use HasEnumLabelsAndOptionsTrait;

    case Normal = 'normal';
    case Bold = 'bold';
    case Bolder = 'bolder';
    case Lighter = 'lighter';
    case W900 = '900';

    public static function getLabel(PluginOptionsInterface $value, string $labelSet): string
    {
        return match ($value) {
            self::Normal => tr('Normal'),
            self::Bold => tr('Bold'),
            self::Bolder => tr('Bolder'),
            self::Lighter => tr('Lighter'),
            self::W900 => tr('Boldest'),
        };
    }
}
