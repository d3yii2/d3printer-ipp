<?php

namespace d3yii2\d3printeripp\components\rules;

use d3yii2\d3printeripp\types\PrinterAttributes;

/**
 * Represents the input tray of a printer and provides methods for retrieving its attributes and status.
 * Data example:
 *  printer-input-tray
 *    class: obray\ipp\types\OctetString
 *    value: type=sheetFeedAutoNonRemovableTray;dimunit=micrometers;mediafeed=297000;mediaxfeed=210000;maxcapacity=-2;level=-2;unit=percent;status=0;name=Tray 1;
 *
 * Constants:
 * - STATUS_OK: Indicates that the input tray is functioning properly.
 * - STATUS_EMPTY_TRY: Indicates that the input tray is empty.
 * - STATUS_MAP: Maps status codes to their corresponding descriptions.
 *
 * Methods:
 * - __construct(int $value): Initializes the input tray with a specific status value.
 * - getAttributeNames(): Returns the attribute name for the input tray.
 * - getLabel(): Retrieves the label for the input tray.
 * - getValueLabel(): Returns the human-readable description of the current status value.
 * - isError(): Determines whether the current status represents an error.
 */
class PrinterInputTray implements RulesInterface
{

    private const STATUS_OK = 0;
    private const STATUS_EMPTY_TRY = 19;
    private const STATUS_NOT_FOUND_IN_CSV = 9999;
    private const STATUS_MAP = [
        self::STATUS_OK => 'Ok',
        self::STATUS_EMPTY_TRY => 'Nav papīra',
        self::STATUS_NOT_FOUND_IN_CSV => 'Nav atrasts CSV',
    ];
    private int $value;

    public function __construct(string $value)
    {
        /**
         * maxcapacity
         *   Meaning: maximum capacity of the tray (usually number of sheets).
         *   Interpretation: how many sheets the tray is designed to hold when full.
         *    Your value: maxcapacity=-2
         *    -2 is commonly used by printers/IPP implementations to mean “unknown / not reported” (i.e., the device isn’t providing a meaningful capacity value).
         * level
         *   Meaning: current fill level of the tray.
         *   How to read it depends on the accompanying unit:
         *   If unit=percent, then level would normally be 0–100 (percentage full).
         *   Some devices instead report an absolute sheet count (and then the unit would reflect that).
         *   Your value: level=-2 with unit=percent
         *    Again, -2 indicates “unknown / not reported” rather than “-2%”.
         * if not found status, its mean is ok
         */
        $this->value = (int)CsvString::get(
            $value,
            'status',
            self::STATUS_OK
        );
    }

    public static function getAttributeName(): string
    {
        return PrinterAttributes::PRINTER_INPUT_TRAY;
    }

    public function getLabel(): string
    {
        return 'Papīra padeve';
    }

    public function getValueLabel()
    {
        return self::STATUS_MAP[$this->value] ?? 'unknown [' . $this->value . ']';
    }

    public function isWarning(): bool
    {
        return false;
    }
    public function isError(): bool
    {
        return $this->value !== self::STATUS_OK;
    }
    public function getWarningMessage(): string
    {
        return $this->isError();
    }

    public function getErrorMessage(): string
    {
        return 'Papra padeves kļūda: "' . $this->getValueLabel() . '"';
    }

    public static function getType(): string
    {
        return self::TYPE_RULE;
    }
}