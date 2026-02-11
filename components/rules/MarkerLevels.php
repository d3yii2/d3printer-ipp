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
class MarkerLevels implements RulesInterface
{


    private int $value;
    public int $minValue = 20;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function getAttributeName(): string
    {
        return PrinterAttributes::MARKER_LEVELS;
    }

    public function getLabel(): string
    {
        return 'Kātridžš';
    }

    public function getValueLabel(): string
    {
        return $this->value . '%';
    }

    public function isWarning(): bool
    {
        return $this->minValue > $this->value;
    }

    public function isError(): bool
    {
        return false;
    }

    public function getWarningMessage(): string
    {
        return $this->isWarning() ? 'Kārtridžā nepietiekams atlikums: ' . $this->getValueLabel() : '';
    }

    public function getErrorMessage(): string
    {
        return '';
    }
}
