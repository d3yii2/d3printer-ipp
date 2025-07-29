<?php

namespace d3yii2\d3printeripp\logic;


use d3yii2\d3printeripp\logic\PrinterAttributes;
use d3yii2\d3printeripp\types\PrinterAttributesTypes;
use obray\ipp\Attribute;
use obray\ipp\enums\PrinterState;
use obray\ipp\Printer as IppPrinterClient;
use d3yii2\d3printeripp\interfaces\PrinterInterface;
use obray\ipp\transport\IPPPayload;
use yii\base\Exception;

/**
 * Base abstract class for printer implementations
 */
abstract class BasePrinter implements PrinterInterface
{
    protected PrinterConfig $config;
    protected IppPrinterClient $client;

    protected ?string $lastError = null;
    protected ?string $printUri = null;
    protected ?IPPPayload $responsePayload = null;

    protected const JOB_ID = 'job-id';
    protected const JOB_STATE = 'job-state';
    protected const JOB_STATE_MESSAGE = 'job-state-message';
    protected const JOB_STATE_REASONS = 'job-state-reasons';
    protected const JOB_URI = 'job-uri';

    public function __construct(PrinterConfig $config)
    {
        $this->config = $config;
        $this->init();
    }

    protected function init(): void
    {
        $this->client = new IppPrinterClient(
            $this->config->getUri(),
            $this->config->getUsername(),
            $this->config->getPassword()
        );
    }

    protected function getClient(): IppPrinterClient
    {
        return $this->client;
    }


    public function isOnline(): bool
    {
        try {
            $status = $this->getStatus();

            // idle|processing|stopped
            return $status !== 'stopped';
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getStatus(): bool
    {
        try {
            $state = PrinterAttributes::getPrinterState($this->config);

            return $state->__toString();

        } catch (\Exception $e) {
            return false;
        }
    }

    public function getPrinterOutputTray(): string
    {

        $try = PrinterAttributes::getPrinterOutputTray($this->config);

        return $try->getAttributeValue(); //->decode($tryAttributeValues);

    }

    public function getSuppliesStatus(): array
    {
        $markerLevels = PrinterAttributes::getMarkerLevels($this->config);
        $markerColors = PrinterAttributes::getMarkerColors($this->config);
        $markerNames = PrinterAttributes::getMarkerNames($this->config);
        $markerTypes = PrinterAttributes::getMarkerTypes($this->config);

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
            'status' => $this->getSupplyStatus($levelValue ?? -1)
        ];

        return $supplies;
    }

    public function getSystemInfo(): array
    {
        $printerInfo = PrinterAttributes::getPrinterInfo($this->config);
        $printerMakeAndModel = PrinterAttributes::getPrinterMakeAndModel($this->config);
        $printerLocation = PrinterAttributes::getPrinterLocation($this->config);
        $deviceUri = PrinterAttributes::getDeviceUri($this->config);


        return [
            'info' => $printerInfo->getAttributeValue(),
            'model' => $printerMakeAndModel->getAttributeValue(),
            'location' => $printerLocation,
            'deviceUri' => $deviceUri->getAttributeValue(),
        ];
    }

    public function printJob(string $document, array $options = []): array
    {
        $currentLimit = ini_get('max_execution_time');
        set_time_limit($this->config->getTimeout());
        $ipp = $this->getClient();
        usleep(1000000);
        $tryCounter = 1;
        while ($tryCounter <= 5) {
            $requestId = 1;
            /** @var IPPPayload $response */
            $response = $ipp->printJob($document, $requestId, $options);
            if ($response->statusCode->getClass() === 'successful') {
                set_time_limit($currentLimit);

                $jobAttributes = $response->jobAttributes->attributes ?? [];

                /** @var Attribute $jobId */
                $jobId = $jobAttributes[self::JOB_ID]->value ?? null;

                /** @var Attribute $jobState */
                $jobState = $jobAttributes[self::JOB_STATE]->value ?? null;

                /** @var Attribute $jobStateMessage */
                $jobStateMessage = $jobAttributes[self::JOB_STATE_MESSAGE]->value ?? null;

                /** @var Attribute $jobStateReasons */
                $jobStateReasons = $jobAttributes[self::JOB_STATE_REASONS]->value ?? null;

                /** @var Attribute $jobUri */
                $jobUri = $jobAttributes[self::JOB_URI]->value ?? null;


                return [
                    self::JOB_ID => $jobId->getAttributeValue(),
                    self::JOB_STATE => $jobState->getAttributeValue(),
                    self::JOB_STATE_MESSAGE => $jobStateMessage->getAttributeValue(),
                    self::JOB_STATE_REASONS => $jobStateReasons->getAttributeValue(),
                    self::JOB_URI => $jobUri->getAttributeValue(),
                ];
            }
            $tryCounter++;
            usleep(1000000);
        }
        set_time_limit($currentLimit);

        throw new Exception('Can not print! ' . PHP_EOL . 'response: ' . $response->statusCode);
    }

    public function getJobs(): array
    {
        $this->client->setOperationId(IPP::GET_JOBS);
        $this->client->addAttribute('requested-attributes', 'job-id');
        $this->client->addAttribute('requested-attributes', 'job-name');
        $this->client->addAttribute('requested-attributes', 'job-state');
        $this->client->addAttribute('requested-attributes', 'job-state-reasons');
        
        $response = $this->client->request();
        
        return $jobAttributes ?? [];
    }

    public function cancelJob(int $jobId): bool
    {
        $this->client->setOperationId(IPP::CANCEL_JOB);
        $this->client->addAttribute('job-id', $jobId);
        
        $response = $this->client->request();
        
        return isset($response['operation-attributes']['status-code']) &&
               $response['operation-attributes']['status-code'] === IPP::SUCCESSFUL_OK;
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

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}