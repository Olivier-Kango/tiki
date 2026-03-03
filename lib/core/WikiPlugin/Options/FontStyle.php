<?php

namespace Tiki\WikiPlugin\Options;

enum FontStyle: string implements PluginOptionsInterface
{
    use HasEnumLabelsAndOptionsTrait;

    case Normal = 'normal';
    case Italic = 'italic';
    case Oblique = 'oblique';

    public static function getLabel(PluginOptionsInterface $value, string $labelSet): string
    {
        return match ($value) {
            self::Normal => tr('Normal'),
            self::Italic => tr('Italic'),
            self::Oblique => tr('Oblique'),
        };
    }
}
