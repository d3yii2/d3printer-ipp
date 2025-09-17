<?php

namespace d3yii2\d3printeripp\logic;

class ValueFormatter
{
    public static function ynFromInt(int $value)
    {
        return $value === 1 ? 'Yes' : 'No';
    }

    public static function ynFromArray(array $arr, string $key)
    {
        if (empty($arr[$key])) {
            return '-';
        }

        return $arr[$key] == 'true' ? 'Yes' : 'No';
    }
}