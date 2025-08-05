<?php

namespace d3yii2\d3printeripp\logic;

use yii\base\Exception;

/**
 * Class PrinterDaemon
 * @package d3yii2\d3printeripp\logic
 */
class PrinterDaemon
{
    private PrinterConfig $printerConfig;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_FAILED = 'failed';
    public const STATUS_UNKNOW = 'unknow';

    private $rawStatus;

    public function __construct(PrinterConfig $printerConfig)
    {
        $this->printerConfig = $printerConfig;
    }

    public function getStatus(): string
    {
//        return self::STATUS_ACTIVE;
        $command = sprintf('systemctl status %s', $this->printerConfig->getDaemonName());
        $output = shell_exec($command);
        $this->rawStatus = $output;
        $status = '-';
        if (preg_match('/Active:\s+(.*?)\s+\(/', $output, $matches)) {
            $status = $matches[1];
            if (in_array($status, [
                self::STATUS_ACTIVE,
                self::STATUS_INACTIVE,
                self::STATUS_FAILED,
            ])) {
                return $status;
            };
        }

        /*throw new Exception(
            sprintf(
                'Cannot parse daemon status value: %s. Printer: %s (%s). Daemon name: "%s". Command: "%s"',
                $status,
                $this->printerConfig->getName(),
                $this->printerConfig->getSlug(),
                $this->printerConfig->getDaemonName(),
                $command
            )
        );*/

        return self::STATUS_UNKNOW;
    }

    public function statusOk(): bool
    {
        if ($this->getStatus() === self::STATUS_ACTIVE) {
            return true;
        }

        $status = $this->getStatus();

        return $status !== self::STATUS_UNKNOW ? $status : sprintf('%s (%s)', $status, $this->getRawStatus());
    }

    public function getRawStatus(): ?string
    {
        return $this->rawStatus;
    }
}
