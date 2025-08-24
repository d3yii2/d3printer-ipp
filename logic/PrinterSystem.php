<?php

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\interfaces\StatusInterface;
use d3yii2\d3printeripp\logic\AlertConfig;
use d3yii2\d3printeripp\logic\PrinterAttributes;
use d3yii2\d3printeripp\logic\PrinterConfig;
use obray\ipp\enums\PrinterState;
use yii\base\Exception;

/**
 * Class PrinterHealth
 * @package d3yii2\d3printeripp\logic
 */
class PrinterSystem implements StatusInterface
{

    protected PrinterConfig $printerConfig;

    protected AlertConfig $alertConfig;

    protected PrinterAttributes $printerAttributes;

    private array $printerStates = [
        PrinterState::stopped => 'stopped',
        PrinterState::idle => 'idle',
        PrinterState::processing => 'processing',
    ];

    public function __construct(PrinterConfig $printerConfig, AlertConfig $alertConfig, PrinterAttributes $printerAttributes)
    {
        $this->printerConfig = $printerConfig;
        $this->alertConfig = $alertConfig;
        $this->printerAttributes = $printerAttributes;
    }

    public function getStatus() : array
    {
        $printerInfo = $this->printerAttributes->getPrinterInfo();
        $printerMakeAndModel = $this->printerAttributes->getPrinterMakeAndModel();
        $printerLocation = $this->printerAttributes->getPrinterLocation();
        $deviceUri = $this->printerConfig->getUri();
        $state = $this->printerAttributes->getPrinterState();

        return [
            'info' => $printerInfo,
            'model' => $printerMakeAndModel,
            'location' => $printerLocation,
            'deviceUri' => $deviceUri,
            'state' => $this->getStateName($state),
            'alive' => $this->isAlive($state)
        ];
    }

    private function getStateName(string $state)
    {
        return $this->printerStates[$state]  ?? 'unknown';
    }

    public function isAlive(string $state): bool
    {
        // idle|processing|stopped
        return $state !== PrinterState::stopped;
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
