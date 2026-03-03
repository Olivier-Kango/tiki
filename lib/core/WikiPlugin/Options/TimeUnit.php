<?php

namespace Tiki\WikiPlugin\Options;

enum TimeUnit: string implements PluginOptionsInterface
{
    use HasEnumLabelsAndOptionsTrait;

    case Hour  = 'hour';
    case Day   = 'day';
    case Week  = 'week';
    case Month = 'month';

    public static function getLabel(PluginOptionsInterface $value, string $labelSet): string
    {
        return match ($value) {
            self::Hour  => tr('Hour'),
            self::Day   => tr('Day'),
            self::Week  => tr('Week'),
            self::Month => tr('Month'),
        };
    }
}
