<?php

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\logic\AlertConfig;
use d3yii2\d3printeripp\logic\PrinterAttributes;
use d3yii2\d3printeripp\logic\PrinterConfig;
use yii\base\Exception;

/**
 * Class PrinterHealth
 * @package d3yii2\d3printeripp\logic
 */
class PrinterHealth
{

    protected PrinterConfig $printerConfig;

    protected AlertConfig $alertConfig;

    protected PrinterAttributes $printerAttributes;

    public function __construct(PrinterConfig $printerConfig, AlertConfig $alertConfig, PrinterAttributes $printerAttributes)
    {
        $this->printerConfig = $printerConfig;
        $this->alertConfig = $alertConfig;
        $this->printerAttributes = $printerAttributes;
    }


    /**
     * @return bool
     * @throws Exception
     */
    public function paperSizeOk(): bool
    {
        return $this->alertConfig->getPaperSize() === $this->printerAttributes->getPaperSize();
    }
    
    /**
     * @return bool
     * @throws Exception
     */
    public function energySleepOk(): bool
    {
        return $this->alertConfig->getSleepAfter() === $this->printerAttributes->getSleepAfter();
    }
    
    /**
     * @return bool
     * @throws Exception
     */
    public function printOrientationOk(): bool
    {
        return $this->alertConfig->getPrintOrientation() === $this->printerAttributes->getPrintOrientation();
    }

    public function statusOk(): bool
    {
        $state = $this->printerAttributes->getPrinterState();

        return in_array(
            $state,
            [self::STATUS_READY, self::STATUS_PRINTING, self::STATUS_SLEEP]
        );
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
}
