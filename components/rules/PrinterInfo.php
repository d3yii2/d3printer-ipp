<?php

namespace d3yii2\d3printeripp\components\rules;

use d3yii2\d3printeripp\components\rules\RulesInterface;
use d3yii2\d3printeripp\types\PrinterAttributes;

class PrinterInfo implements RulesInterface
{


    private string $value;


    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function getAttributeName(): string
    {
        return PrinterAttributes::PRINTER_INFO;
    }
    public function getLabel(): string
    {
        return 'Informācija';
    }

    public function getValueLabel(): string
    {
        return $this->value;
    }

    public function isWarning(): bool
    {
        return false;
    }

    public function isError(): bool
    {
        return false;
    }

    public function getWarningMessage(): string
    {
        return '';
    }

    public function getErrorMessage(): string
    {
        return '';
    }
}