<?php

namespace Tiki\WikiPlugin\Options;

enum BorderStyle: string implements PluginOptionsInterface
{
    use HasEnumLabelsAndOptionsTrait;

    case None = 'none';
    case Hidden = 'hidden';
    case Dotted = 'dotted';
    case Dashed = 'dashed';
    case Solid = 'solid';
    case Double = 'double';
    case Groove = 'groove';
    case Ridge = 'ridge';
    case Inset = 'inset';
    case Outset = 'outset';

    public static function getLabel(PluginOptionsInterface $value, string $labelSet): string
    {
        return match ($value) {
            self::None => tr('None'),
            self::Hidden => tr('Hidden'),
            self::Dotted => tr('Dotted'),
            self::Dashed => tr('Dashed'),
            self::Solid => tr('Solid'),
            self::Double => tr('Double'),
            self::Groove => tr('Groove'),
            self::Ridge => tr('Ridge'),
            self::Inset => tr('Inset'),
            self::Outset => tr('Outset'),
        };
    }
}
