<?php
declare(strict_types=1);

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

    public function __construct(
        PrinterConfig $printerConfig,
        AlertConfig $alertConfig,
        PrinterAttributes $printerAttributes
    ) {
        $this->printerConfig = $printerConfig;
        $this->alertConfig = $alertConfig;
        $this->printerAttributes = $printerAttributes;
    }

    /**
     * @return array{info: mixed|string, model: mixed|string, location: mixed|string, deviceUri: mixed|string,
     *      host: mixed|null|string, state: string, status: string}
     * @throws Exception
     */
    public function getStatus(): array
    {
        $printerData = $this->gatherPrinterData();

        return [
            'info' => $printerData['info'],
            'name' => $this->printerConfig->getName(),
            'model' => $printerData['model'],
            'location' => $printerData['location'],
            'deviceUri' => $printerData['deviceUri'],
            'host' => $printerData['host'],
            'state' => $this->getStateName($printerData['state']),
            'status' => PrinterSystem::isAlive($printerData['state']) ? ValueFormatter::UP : ValueFormatter::DOWN,
        ];
    }

    /**@return array{host: null|string, info: string, model: string, location: string, deviceUri: string, state: string}
     * @throws Exception
     */
    private function gatherPrinterData(): array
    {
        return [
            'host' => $this->printerConfig->getHost(),
            'info' => $this->printerAttributes->getPrinterInfo(),
            'model' => $this->printerAttributes->getPrinterMakeAndModel(),
            'location' => $this->printerAttributes->getPrinterLocation(),
            'deviceUri' => $this->printerConfig->getUri(),
            'state' => $this->printerAttributes->getPrinterState()
        ];
    }

    /**
     * @param string $state
     * @return string
     */
    private function getStateName(string $state): string
    {
        return $this->printerStates[$state] ?? 'unknown';
    }

    /**
     * @param string $state
     * @return bool
     */
    public static function isAlive(string $state): bool
    {
        // idle|processing|stopped
        return $state != PrinterState::stopped;
    }
}
