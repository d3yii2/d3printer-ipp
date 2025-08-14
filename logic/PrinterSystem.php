<?php

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\interfaces\StatusDataInterface;
use d3yii2\d3printeripp\logic\AlertConfig;
use d3yii2\d3printeripp\logic\PrinterAttributes;
use d3yii2\d3printeripp\logic\PrinterConfig;
use obray\ipp\enums\PrinterState;
use yii\base\Exception;

/**
 * Class PrinterHealth
 * @package d3yii2\d3printeripp\logic
 */
class PrinterSystem implements StatusDataInterface
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

    public function buildStats() : array
    {
        $attributes = $this->printerAttributes->getAll();
        $printerInfo = $attributes->getPrinterInfo();
        $printerMakeAndModel = $attributes->getPrinterMakeAndModel();
        $printerLocation = $attributes->getPrinterLocation();
        $deviceUri = $attributes->getDeviceUri();
        $state = $attributes->getPrinterState();

        return [
            'info' => $printerInfo,
            'model' => $printerMakeAndModel,
            'location' => $printerLocation,
            'deviceUri' => $deviceUri,
            'state' => $state,
            'alive' => $this->isAlive()
        ];
    }


    public function isAlive(): bool
    {
        $status = $this->getStatus();

        // idle|processing|stopped
        return $status !== PrinterState::stopped;
    }

    /**
     * @return bool
     * @throws Exception
     */
    /*public function energySleepOk(): bool
    {
        return $this->alertConfig->getSleepAfter() === $this->printerAttributes->getSleepAfter();
    }*/
}
