<?php
declare(strict_types=1);

namespace d3yii2\d3printeripp\logic;

use yii\base\Exception;
use d3yii2\d3printeripp\interfaces\StatusInterface;

/**
 * Class PrinterSupplies
 * @package d3yii2\d3printeripp\logic
 */
class PrinterSupplies implements StatusInterface
{
    private const LEVEL_UNKNOWN_1 = '-1';
    private const LEVEL_UNKNOWN_2 = '-2';
    private const LEVEL_UNKNOWN_3 = '-3';
    private const LEVEL_LOW_THRESHOLD = '10';
    private const LEVEL_MEDIUM_THRESHOLD = '25';

    private const SUPPLY_STATUS_UNKNOWN = 'unknown';
    private const SUPPLY_STATUS_LOW = 'low';
    private const SUPPLY_STATUS_MEDIUM = 'medium';

    public const STATUS_MARKER_NAME = 'marker-name';
    public const STATUS_MARKER_LEVEL = 'marker-level';
    public const STATUS_MARKER_COLOR = 'marker-color';
    public const STATUS_MARKER_TYPE = 'marker-type';
    public const STATUS_DOCUMENT_SIZE = 'document-size';

    protected PrinterConfig $printerConfig;
    protected PrinterAttributes $printerAttributes;
    protected AlertConfig $alertConfig;

    private array $errors = [];

    /**
     * @param PrinterConfig $printerConfig
     * @param PrinterAttributes $printerAttributes
     * @param AlertConfig $alertConfig
     */
    public function __construct(
        PrinterConfig $printerConfig,
        PrinterAttributes $printerAttributes,
        AlertConfig $alertConfig
    ) {
        $this->printerConfig = $printerConfig;
        $this->printerAttributes = $printerAttributes;
        $this->alertConfig = $alertConfig;
    }

    /**@return array{name: string, color: null|string, type: null|string, level: string, documentSize: string}
     * @throws Exception
     */
    public function getStatus(): array
    {
        $markerLevel = $this->printerAttributes->getMarkerLevels();
        $markerColor = $this->printerAttributes->getMarkerColors();
        $markerName = $this->printerAttributes->getMarkerNames();
        $markerType = $this->printerAttributes->getMarkerTypes();

        return [
            self::STATUS_MARKER_NAME => $markerName,
            self::STATUS_MARKER_COLOR => $markerColor,
            self::STATUS_MARKER_TYPE => $markerType,
            self::STATUS_MARKER_LEVEL => $this->getSupplyStatus($markerLevel),
            self::STATUS_DOCUMENT_SIZE => $this->printerAttributes->getDocumentSize(),
            'errors' => $this->getErrors(),
        ];
    }

    /**
     * @throws Exception
     */
    public function paperSizeOk(): bool
    {
        return $this->alertConfig->getDocumentSize() === $this->printerAttributes->getDocumentSize();
    }

    /**
     * @throws Exception
     */
    public function cartridgeOk(): bool
    {
        //@TODO - jānoskidro vai ir pareizs kārtridžs
        $currentValue = $this->printerAttributes->getMarkerLevels();
        $minValue = $this->alertConfig->getCartridgeMinValue();
        return $currentValue > $minValue;
    }

    /**
     * @throws Exception
     */
    public function drumOk(): bool
    {
        $currentValue = $this->printerAttributes->getDrumLevel();
        $minValue = $this->alertConfig->getDrumMinValue();
        return $currentValue > $minValue;
    }

    /**
     * @throws Exception
     */
    public function printOrientationOk(): bool
    {
        return $this->alertConfig->getPrintOrientation() === $this->printerAttributes->getPrintOrientation();
    }

    /**
     * @throws Exception
     */
    public function getPrinterOutputTray(): string
    {
        $try = $this->printerAttributes->getPrinterOutputTray();
        return $try->getAttributeValue();
    }

    /**
     * @param string $level
     * @return string
     */
    protected function getSupplyStatus(string $level): string
    {
        if (in_array($level, [self::LEVEL_UNKNOWN_1, self::LEVEL_UNKNOWN_2, self::LEVEL_UNKNOWN_3])) {
            return self::SUPPLY_STATUS_UNKNOWN;
        }

        if ($level <= self::LEVEL_LOW_THRESHOLD) {
            return self::SUPPLY_STATUS_LOW;
        }

        if ($level <= self::LEVEL_MEDIUM_THRESHOLD) {
            return self::SUPPLY_STATUS_MEDIUM;
        }

        return $level;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
