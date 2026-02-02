<?php

namespace d3yii2\d3printeripp\enums;

class PrinterState extends \obray\ipp\enums\PrinterState
{
    private const PRINTER_STATE_MAP = [
        \obray\ipp\enums\PrinterState::stopped => 'stopped',
        \obray\ipp\enums\PrinterState::idle => 'idle',
        \obray\ipp\enums\PrinterState::processing => 'processing',
    ];

    public static function getLabel(int $state): string
    {
        return self::PRINTER_STATE_MAP[$state] ?? 'unknown';
    }
}