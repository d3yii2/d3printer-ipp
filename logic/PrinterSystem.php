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

    private array $errors = [];

    public const STATUS_HOST = 'host';
    public const STATUS_INFO = 'info';
    public const STATUS_NAME = 'name';
    public const STATUS_MODEL = 'model';
    public const STATUS_DEVICE_URI = 'device-uri';
    public const STATUS_LOCATION = 'location';
    public const STATUS_STATE_NAME = 'state-name';
    public const STATUS_UP_DOWN = 'up-down';

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
        $gatherStates = $this->printerConfig->getGatherStates();

        if (empty($gatherStates['PrinterSystem'])) {
            return [];
        }

        $gatherStates = $gatherStates['PrinterSystem'];

        // Make request to printer to get all attributes if needed
        if (
            in_array(self::STATUS_INFO,  $gatherStates)
            || in_array(self::STATUS_MODEL,  $gatherStates)
            || in_array(self::STATUS_LOCATION,  $gatherStates)
            || in_array(self::STATUS_STATE_NAME,  $gatherStates)
            || in_array(self::STATUS_UP_DOWN,  $gatherStates)
        ) {
            $this->printerAttributes->getAll();
        }

        $returnStates = [];

        if (in_array(self::STATUS_NAME, $gatherStates)) {
            $returnStates[self::STATUS_NAME] = $this->printerConfig->getName();
        }

        if (in_array(self::STATUS_HOST, $gatherStates)) {
            $returnStates[self::STATUS_HOST] = $this->printerConfig->getHost();
        }

        if (in_array(self::STATUS_INFO, $gatherStates)) {
            $returnStates[self::STATUS_INFO] = $this->printerAttributes->getPrinterInfo();
        }

        if (in_array(self::STATUS_MODEL, $gatherStates)) {
            $returnStates[self::STATUS_MODEL] = $this->printerAttributes->getPrinterMakeAndModel();
        }

        if (in_array(self::STATUS_LOCATION, $gatherStates)) {
            $returnStates[self::STATUS_LOCATION] = $this->printerAttributes->getPrinterLocation();
        }

        if (in_array(self::STATUS_DEVICE_URI, $gatherStates)) {
            $returnStates[self::STATUS_DEVICE_URI] = $this->printerConfig->getUri();
        }

        if (in_array(self::STATUS_STATE_NAME, $gatherStates)) {
            $returnStates[self::STATUS_STATE_NAME] = $this->getStateName($this->printerAttributes->getPrinterState());
        }

        if (in_array(self::STATUS_UP_DOWN, $gatherStates)) {

            $stateName = $this->getStateName($this->printerAttributes->getPrinterState());

            $returnStates[self::STATUS_UP_DOWN] = PrinterSystem::isAlive($stateName)
                ? ValueFormatter::UP
                : ValueFormatter::DOWN;
        }

        $returnStates['errors'] = $this->getErrors();
        
        return $returnStates;
    }

    /**
     * @return string[]
     */
    public function getLabels(): array
    {
        return [
            self::STATUS_NAME => 'Name',
            self::STATUS_HOST => 'Host',
            self::STATUS_INFO => 'Info',
            self::STATUS_MODEL => 'Model',
            self::STATUS_DEVICE_URI => 'Device URI',
            self::STATUS_LOCATION => 'Location',
            self::STATUS_STATE_NAME => 'State',
            self::STATUS_UP_DOWN => 'Up/Down',
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

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
