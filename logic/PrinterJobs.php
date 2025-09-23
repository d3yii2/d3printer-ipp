<?php
declare(strict_types=1);
namespace d3yii2\d3printeripp\logic;

use obray\ipp\Printer as IppPrinterClient;
use obray\ipp\transport\IPPPayload;
use obray\transport\IPP;
use yii\base\Exception;

/**
 * work only on windows
 * load html page, convert ot PHP and send to windows printer
 *
 * Class Printer
 */
class PrinterJobs
{
    protected PrinterConfig $printerConfig;
    protected IppPrinterClient $client;

    protected const JOB_ID = 'job-id';
    protected const JOB_STATE = 'job-state';
    protected const JOB_STATE_MESSAGE = 'job-state-message';
    protected const JOB_STATE_REASONS = 'job-state-reasons';
    protected const JOB_URI = 'job-uri';

    private const MAX_RETRY_ATTEMPTS = 5;
    private const RETRY_DELAY_MICROSECONDS = 1000000; // 1 second
    private const PRINT_TIMEOUT_SECONDS = 60;

    /**
     * @param PrinterConfig $config
     * @param IppPrinterClient $client
     */
    public function __construct(PrinterConfig $config, IppPrinterClient $client)
    {
        $this->printerConfig = $config;
        $this->client = $client;
    }

    /**
     * @throws Exception
     */
    public function print(string $document, array $options = []): array
    {
        $originalTimeLimit = $this->setTimeLimit(self::PRINT_TIMEOUT_SECONDS);

        try {
            usleep(self::RETRY_DELAY_MICROSECONDS);

            for ($attempt = 1; $attempt <= self::MAX_RETRY_ATTEMPTS; $attempt++) {
                $response = $this->attemptPrintJob($document, $options);

                if (PrinterJobs::isPrintSuccessful($response)) {
                    return PrinterJobs::extractJobAttributes($response);
                }

                if ($attempt < self::MAX_RETRY_ATTEMPTS) {
                    usleep(self::RETRY_DELAY_MICROSECONDS);
                }
            }

            throw new Exception(
                'Cannot print after ' . self::MAX_RETRY_ATTEMPTS
                . ' attempts. Last response: ' . ( $response->statusCode ?? 'unknown' )
            );
        } finally {
            set_time_limit($originalTimeLimit);
        }
    }

    /**
     * @return IPPPayload
     */
    public function getAllJobs(): IPPPayload
    {
        return $this->client->getJobs();
    }

    /**
     * @return IPPPayload
     */
    public function purgeAllJobs(): IPPPayload
    {
        return $this->client->purgeJobs();
    }

    /**
     * @param int $jobId
     * @return bool
     */
    public function cancelJob(int $jobId): bool
    {
        $this->client->setOperationId(IPP::CANCEL_JOB);
        $this->client->addAttribute('job-id', $jobId);

        $response = $this->client->request();

        return isset($response['operation-attributes']['status-code']) &&
            $response['operation-attributes']['status-code'] === IPP::SUCCESSFUL_OK;
    }

    /**
     * @param int $seconds
     * @return int
     */
    private function setTimeLimit(int $seconds): int
    {
        $currentLimit = (int)ini_get('max_execution_time');
        set_time_limit($seconds);
        return $currentLimit;
    }

    /**
     * @param string $document
     * @param array $options
     * @return IPPPayload
     */
    private function attemptPrintJob(string $document, array $options): IPPPayload
    {
        return $this->client->printJob($document, 1, $options);
    }

    /**
     * @param IPPPayload $response
     * @return bool
     */
    private static function isPrintSuccessful(IPPPayload $response): bool
    {
        return $response->statusCode->getClass() === 'successful';
    }

    /**
     * @return array{PrinterJobs.JOB_ID: mixed,
     * \d3yii2\d3printeripp\logic\PrinterJobs.JOB_STATE: mixed,
     * \d3yii2\d3printeripp\logic\PrinterJobs.JOB_STATE_MESSAGE: mixed,
     * \d3yii2\d3printeripp\logic\PrinterJobs.JOB_STATE_REASONS: mixed,
     * \d3yii2\d3printeripp\logic\PrinterJobs.JOB_URI: mixed}
     */
    private static function extractJobAttributes(IPPPayload $response): array
    {
        $jobAttributes = $response->jobAttributes->attributes ?? [];

        return [
            self::JOB_ID => $jobAttributes[self::JOB_ID]->value ?? null,
            self::JOB_STATE => $jobAttributes[self::JOB_STATE]->value ?? null,
            self::JOB_STATE_MESSAGE => $jobAttributes[self::JOB_STATE_MESSAGE]->value ?? null,
            self::JOB_STATE_REASONS => $jobAttributes[self::JOB_STATE_REASONS]->value ?? null,
            self::JOB_URI => $jobAttributes[self::JOB_URI]->value ?? null,
        ];
    }
}
