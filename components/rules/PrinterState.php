<?php

namespace d3yii2\d3printeripp\components\rules;

use d3yii2\d3printeripp\types\PrinterAttributes;

class PrinterState extends \obray\ipp\enums\PrinterState implements RulesInterface
{


    private const PRINTER_STATE_MAP = [
        \obray\ipp\enums\PrinterState::stopped => 'stopped',
        \obray\ipp\enums\PrinterState::idle => 'idle',
        \obray\ipp\enums\PrinterState::processing => 'processing',
    ];

    public static function getAttributeName(): string
    {
        return PrinterAttributes::PRINTER_STATE;
    }
    public function getLabel(): string
    {
        return 'Statuss';
    }

    public function getValueLabel(): string
    {
        return self::PRINTER_STATE_MAP[$this->value] ?? 'unknown';
    }

    public function isWarning(): bool
    {
        return false;
    }

    public function isError(): bool
    {
        return $this->value === self::stopped;
    }

    public function getWarningMessage(): string
    {
        return $this->isError();
    }

    public function getErrorMessage(): string
    {
        return 'Problēma: "' . $this->getValueLabel() . '"';
    }

    public static function getType(): string
    {
        return self::TYPE_RULE;
    }
}