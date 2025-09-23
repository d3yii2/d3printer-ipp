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

    private const STATUS_UNKNOWN = 'unknown';
    private const STATUS_LOW = 'low';
    private const STATUS_MEDIUM = 'medium';

    protected PrinterConfig $printerConfig;
    protected PrinterAttributes $printerAttributes;
    protected AlertConfig $alertConfig;

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
            'name' => $markerName,
            'color' => $markerColor,
            'type' => $markerType,
            'level' => $this->getSupplyStatus($markerLevel),
            'documentSize' => $this->printerAttributes->getDocumentSize()
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
            return self::STATUS_UNKNOWN;
        }

        if ($level <= self::LEVEL_LOW_THRESHOLD) {
            return self::STATUS_LOW;
        }

        if ($level <= self::LEVEL_MEDIUM_THRESHOLD) {
            return self::STATUS_MEDIUM;
        }

        return $level;
    }
}
