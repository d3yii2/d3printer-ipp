<?php

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\interfaces\StatusInterface;
use obray\ipp\enums\PrinterState;
use yii\base\Exception;

/**
 * Class PrinterSystem
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

    public function getStatus(): array
    {
        $printerData = $this->gatherPrinterData();

        return [
            'info' => $printerData['info'],
            'model' => $printerData['model'],
            'location' => $printerData['location'],
            'deviceUri' => $printerData['deviceUri'],
            'state' => $this->getStateName($printerData['state']),
            'alive' => $this->isAlive($printerData['state'])
        ];
    }

    private function gatherPrinterData(): array
    {
        return [
            'info' => $this->printerAttributes->getPrinterInfo(),
            'model' => $this->printerAttributes->getPrinterMakeAndModel(),
            'location' => $this->printerAttributes->getPrinterLocation(),
            'deviceUri' => $this->printerConfig->getUri(),
            'state' => $this->printerAttributes->getPrinterState()
        ];
    }

    private function getStateName(string $state): string
    {
        return $this->printerStates[$state] ?? 'unknown';
    }

    public function isAlive(string $state): bool
    {
        // idle|processing|stopped
        return $state !== PrinterState::stopped;
    }
}