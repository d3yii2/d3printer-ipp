<?php

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\interfaces\StatusInterface;
use yii\base\Exception;

/**
 * Class PrinterDaemon
 * @package d3yii2\d3printeripp\logic
 */
class PrinterDaemon implements StatusInterface
{
    private PrinterConfig $printerConfig;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_FAILED = 'failed';
    public const STATUS_UNKNOWN = 'unknown';

    private ?string $rawStatus = null;

    public function __construct(PrinterConfig $printerConfig)
    {
        $this->printerConfig = $printerConfig;
    }

    public function getStatus(): array
    {
        return [
            'status' => $this->getState(),
        ];
    }

    public function getState(): string
    {
        $command = sprintf('systemctl status %s', $this->printerConfig->getDaemonName());
        $output = $this->executeCommand($command);

        return $this->parseStatusFromOutput($output);
    }

    private function executeCommand(string $command): ?string
    {
        $output = shell_exec($command);
        $this->rawStatus = $output;

        return $output;
    }

    private function parseStatusFromOutput(?string $output): string
    {
        if ($output === null) {
            return self::STATUS_UNKNOWN;
        }

        if (preg_match('/Active:\s+(.*?)\s+\(/', $output, $matches)) {
            $status = $matches[1];
            if (in_array($status, [
                self::STATUS_ACTIVE,
                self::STATUS_INACTIVE,
                self::STATUS_FAILED,
            ])) {
                return $status;
            }
        }

        return self::STATUS_UNKNOWN;
    }

    public function statusOk(): bool
    {
        $currentStatus = $this->getState();

        return $currentStatus === self::STATUS_ACTIVE;
    }

    public function getRawStatus(): ?string
    {
        return $this->rawStatus;
    }
}