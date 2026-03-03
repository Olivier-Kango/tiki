<?php

namespace Tiki\WikiPlugin\Options;

class BooleanNormalizer
{
    public static function isTruthy(mixed $value): bool
    {
        return ($value == BooleanEnglishLetter::Yes->value || filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
    }

    public static function isFalsy(mixed $value): bool
    {
        return ! self::isTruthy($value);
    }
}
