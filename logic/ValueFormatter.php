<?php
declare(strict_types=1);

namespace d3yii2\d3printeripp\logic;

use yii\helpers\Html;
use Yii;

/**
 *
 */
class ValueFormatter
{
    public const YES = 'Yes';
    public const NO = 'No';
    public const UNKNOWN = 'Unknown';

    public const UP = 'Up';
    public const DOWN = 'Down';

    /**
     * @param int $value
     * @param int $dangerValue
     * @param string $colorOk
     * @param string $colorDanger
     * @return string
     */
    public static function coloredDangerLessValue(
        int $value,
        int $dangerValue = 50,
        string $colorOk = 'darkgreen',
        string $colorDanger = 'red'
    ): string
    {
        $colorClass = $value < $dangerValue ? $colorDanger : $colorOk;

        return Html::tag('span', Yii::t('d3printeripp', $value), ['style' => 'color:' . $colorClass]);
    }
    public static function coloredDangerMoreValue(
        int $value,
        int $dangerValue = 50,
        string $colorOk = 'darkgreen',
        string $colorDanger = 'red'
    ): string
    {
        $colorClass = $value > $dangerValue ? $colorDanger : $colorOk;

        return Html::tag('span', Yii::t('d3printeripp', $value), ['style' => 'color:' . $colorClass]);
    }

    /**
     * @param string $value
     * @param string $colorY
     * @param string $colorN
     * @return string
     */
    public static function coloredYnValue(string $value, string $colorY = 'darkgreen', string $colorN = 'red'): string
    {
        $colorClass = $value === self::YES ? $colorY : $colorN;

        return Html::tag('span', Yii::t('d3printeripp', $value), ['style' => 'color:' . $colorClass]);
    }

    /**
     * @param string $value
     * @param string $colorUp
     * @param string $colorDown
     * @return string
     */
    public static function coloredUpDownValue(
        string $value,
        string $colorUp = 'darkgreen',
        string $colorDown = 'red'
    ): string
    {
        $colorClass = $value === self::UP ? $colorUp : $colorDown;

        return Html::tag('span', Yii::t('d3printeripp', $value), ['style' => 'color:' . $colorClass]);
    }

    /**
     * @param int $value
     * @param int $dangerValue
     * @param bool $colored
     * @return string
     */
    public static function ynFromInt(int $value, int $dangerValue, bool $colored = false): string
    {
        $msg = $value === 1 ? self::YES : self::NO;

        return $colored ? self::coloredDangerLessValue($value, $dangerValue) : $msg;
    }

    /**
     * @param array $arr
     * @param string $key
     * @param bool $colored
     * @return string
     */
    public static function ynFromArray(array $arr, string $key, bool $colored = false): string
    {
        $msg = '-';

        if (!empty($arr[$key])) {
            $msg = $arr[$key] == 'true' ? self::YES : self::NO;
        }

        return $colored ? self::coloredYnValue($msg) : $msg;
    }
}
