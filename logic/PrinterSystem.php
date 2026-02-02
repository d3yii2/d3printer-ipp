<?php
declare(strict_types=1);

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\interfaces\StatusInterface;
use obray\ipp\enums\PrinterState;
use obray\ipp\exceptions\AuthenticationError;
use obray\ipp\exceptions\HTTPError;
use yii\base\Exception;

/**
 * get requested information from PrinterAttributes
 * Class PrinterSystem
 * @package d3yii2\d3printeripp\logic
 */
class PrinterSystem implements StatusInterface
{
    protected PrinterAttributes $printerAttributes;

    private array $printerStates = [
        PrinterState::stopped => 'stopped',
        PrinterState::idle => 'idle',
        PrinterState::processing => 'processing',
    ];

    private array $errors = [];

    public const STATUS_INFO = 'info';
    public const STATUS_MODEL = 'model';
    public const STATUS_LOCATION = 'location';
    public const STATUS_STATE_NAME = 'state-name';
    public const STATUS_UP_DOWN = 'up-down';

    public function __construct(
        PrinterAttributes $printerAttributes
    ) {
        $this->printerAttributes = $printerAttributes;
    }

    /**
     * @param array $gatherStates - which states to gather
     * @return string[]
     * @throws Exception
     * @throws AuthenticationError
     * @throws HTTPError
     */
    public function getStatus(array $gatherStates): array
    {

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

        if (in_array(self::STATUS_INFO, $gatherStates)) {
            $returnStates[self::STATUS_INFO] = $this->printerAttributes->getPrinterInfo();
        }

        if (in_array(self::STATUS_MODEL, $gatherStates)) {
            $returnStates[self::STATUS_MODEL] = $this->printerAttributes->getPrinterMakeAndModel();
        }

        if (in_array(self::STATUS_LOCATION, $gatherStates)) {
            $returnStates[self::STATUS_LOCATION] = $this->printerAttributes->getPrinterLocation();
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
            self::STATUS_INFO => 'Info',
            self::STATUS_MODEL => 'Model',
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
