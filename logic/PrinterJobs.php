<?php


namespace d3yii2\d3printeripp\logic;


use d3system\helpers\D3FileHelper;
use d3yii2\d3printeripp\logic\PrinterConfig;
use obray\ipp\Attribute;
use obray\ipp\Printer as IppPrinterClient;
use obray\ipp\transport\IPPPayload;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * work only on windows
 * load html page, convert ot PHP and send to windows printer
 *
 * Class Printer
 */
class PrinterJobs
{
    protected PrinterConfig $printerConfig;

    protected const JOB_ID = 'job-id';
    protected const JOB_STATE = 'job-state';
    protected const JOB_STATE_MESSAGE = 'job-state-message';
    protected const JOB_STATE_REASONS = 'job-state-reasons';
    protected const JOB_URI = 'job-uri';

    protected IppPrinterClient $client;

    public function __construct(PrinterConfig $config, IppPrinterClient $client)
    {
        $this->printerConfig = $config;
        $this->client = $client;
    }

    public function print(string $document, array $options = []): array
    {
        $currentLimit = ini_get('max_execution_time');
        set_time_limit($this->config->getTimeout());
        usleep(1000000);
        $tryCounter = 1;
        while ($tryCounter <= 5) {
            $requestId = 1;
            /** @var IPPPayload $response */
            $response = $this->client->printJob($document, $requestId, $options);
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

    public function getAll(): array
    {
        $jobs = $this->client->getJobs();

        return $jobs;
    }

    public function purgeAll(): IPPPayload
    {
        $jobs = $this->client->purgeJobs();

        return $jobs;
    }

    public function cancel(int $jobId): bool
    {
        $this->client->setOperationId(IPP::CANCEL_JOB);
        $this->client->addAttribute('job-id', $jobId);

        $response = $this->client->request();

        return isset($response['operation-attributes']['status-code']) &&
            $response['operation-attributes']['status-code'] === IPP::SUCCESSFUL_OK;
    }

}