<?php

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\logic\AlertConfig;
use d3yii2\d3printeripp\logic\PrinterAttributes;
use d3yii2\d3printeripp\logic\PrinterConfig;
use yii\base\Exception;
use d3yii2\d3printeripp\interfaces\StatusInterface;

/**
 * Class PrinterHealth
 * @package d3yii2\d3printeripp\logic
 */
class PrinterSupplies implements StatusInterface
{

    protected PrinterConfig $printerConfig;


    protected PrinterAttributes $printerAttributes;

    public function __construct(PrinterConfig $printerConfig, PrinterAttributes $printerAttributes)
    {
        $this->printerConfig = $printerConfig;
        $this->printerAttributes = $printerAttributes;
    }

    public function getStatus(): array
    {

        $markerLevel = $this->printerAttributes->getMarkerLevels();
        $markerColor = $this->printerAttributes->getMarkerColors();
        $markerName = $this->printerAttributes->getMarkerNames();
        $markerType = $this->printerAttributes->getMarkerTypes();
        $supplies = [
            'name' => $markerName  ?? 'Unknown',
            'color' => $markerColor ?? null,
            'type' => $markerType ?? null,
            'level' => $this->getSupplyStatus((int) $markerLevel  ?? -1),
            'documentSize' => $this->printerAttributes->getDocumentSize()
        ];

        return $supplies;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function paperSizeOk(): bool
    {
        return $this->alertConfig->getDocumentSize() === $this->printerAttributes->getDocumentSize();
    }

    /**
     * @return bool
     */
    public function cartridgeOk(): bool
    {
        //@TODO - jānoskidro vai ir pareizs kārtridžs
        $currentValue = $this->printerAttributes->getMarkerLevels();
        $minValue = $this->alertConfig->getCartridgeMinValue();

        return $currentValue > $minValue;
    }


    /**
     * @return bool
     */
    public function drumOk(): bool
    {
        $currentValue = $this->printerAttributes->getDrumLevel();
        $minValue = $this->alertConfig->getDrumMinValue();

        return $currentValue > $minValue;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function printOrientationOk(): bool
    {
        return $this->alertConfig->getPrintOrientation() === $this->printerAttributes->getPrintOrientation();
    }

    public function getPrinterOutputTray(): string
    {
        $try = $this->printerAttributes->getPrinterOutputTray();

        return $try->getAttributeValue(); //->decode($tryAttributeValues);
    }

    protected function getSupplyStatus(int $level): string
    {
        if ($level === -1) return 'unknown';
        if ($level === -2) return 'unknown';
        if ($level === -3) return 'unknown';
        if ($level <= 10) return 'low';
        if ($level <= 25) return 'medium';
        return 'ok (' . $level .  ')';
    }

}
