<?php

namespace d3yii2\d3printeripp\components\rules;

use d3yii2\d3printeripp\types\PrinterAttributes;

class PrinterStateReasons implements RulesInterface
{

    private const REASON_NONE = 'none';
    private const REASONS_MAP = [
        self::REASON_NONE => 'Nekas nav',
        'media-empty' => 'Nav Padeve',
        'media-empty-error' => 'Nav Padeve / Kļūda',
        'media-needed' => 'Nav Papīra',
        'media-needed-error' => 'Nav Papīra / Kļūda',
        'toner-low' => 'Maz Tonera',
        'toner-empty' => 'Nav Tonera',
        'door-open' => 'Durvis atvērtas',
        'jamed' => 'Papīrs iesprūdis',
        'spool-area-full-report' => 'Spūleris pilns',
    ];
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function getAttributeName(): string
    {
        return PrinterAttributes::PRINTER_STATE_REASONS;
    }

    public function getLabel(): string
    {
        return 'Iemesls';
    }

    public function getValueLabel(): string
    {
        return self::REASONS_MAP[$this->value] ?? 'unknown';
    }

    public function isWarning(): bool
    {
        return false;
    }

    public function isError(): bool
    {
        return $this->value !== self::REASON_NONE;
    }

    public function getWarningMessage(): string
    {
        return $this->isError();
    }

    public function getErrorMessage(): string
    {
        return 'Nestrādā: ' . $this->getValueLabel();
    }
}