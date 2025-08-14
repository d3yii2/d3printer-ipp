<?php

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\logic\AlertConfig;
use d3yii2\d3printeripp\logic\PrinterAttributes;
use d3yii2\d3printeripp\logic\PrinterConfig;
use yii\base\Exception;
use d3yii2\d3printeripp\interfaces\StatusDataInterface;

/**
 * Class PrinterHealth
 * @package d3yii2\d3printeripp\logic
 */
class PrinterSupplies implements StatusDataInterface
{

    protected PrinterConfig $printerConfig;


    protected PrinterAttributes $printerAttributes;

    public function __construct(PrinterConfig $printerConfig, PrinterAttributes $printerAttributes)
    {
        $this->printerConfig = $printerConfig;
        $this->printerAttributes = $printerAttributes;
    }

    public function buildStats(): array
    {
        /** @var PrinterAttributes $attributes */
        $attributes = $this->printerAttributes->getAll();

        $markerLevels = $attributes->getMarkerLevels();
        $markerColors = $attributes->getMarkerColors();
        $markerNames = $attributes->getMarkerNames();
        $markerTypes = $attributes->getMarkerTypes();

        $nameValue = $markerNames->getAttributeValue();
        $levelValue = $markerLevels->getAttributeValue();
        $colorValue = $markerColors->getAttributeValue();
        $nameValue = $markerNames->getAttributeValue();
        $typeValue = $markerTypes->getAttributeValue();

        $supplies = [
            'name' => $nameValue ?? 'Unknown',
            'level' => $levelValue ?? -1,
            'color' => $colorValue ?? null,
            'type' => $typeValue ?? null,
            'status' => $this->getSupplyStatus($levelValue ?? -1),
            'documentSize' => $attributes->getDocumentSize()
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
        return 'ok';
    }

}
