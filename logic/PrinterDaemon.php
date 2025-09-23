<?php
declare(strict_types=1);

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\interfaces\StatusInterface;

/**
 * Class PrinterDaemon
 * @package d3yii2\d3printeripp\logic
 */
class PrinterDaemon implements StatusInterface
{
    private PrinterConfig $printerConfig;

    public const STATUS_ACTIVE = 'Active';
    public const STATUS_INACTIVE = 'Inactive';
    public const STATUS_FAILED = 'Failed';
    public const STATUS_UNKNOWN = 'Unknown';

    private ?string $rawStatus = null;

    public function __construct(PrinterConfig $printerConfig)
    {
        $this->printerConfig = $printerConfig;
    }

    /**
     * @return array{status: string}
     */
    public function getStatus(): array
    {
        return [
            'status' => $this->getState()
        ];
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        $command = sprintf('systemctl status %s', $this->printerConfig->getDaemonName());
        $output = $this->executeCommand($command);

        return PrinterDaemon::parseStatusFromOutput($output);
    }

    private function executeCommand(string $command): ?string
    {
        $output = shell_exec($command);
        $this->rawStatus = $output;

        return $output;
    }

    /**
     * @param string|null $output
     * @return string
     */
    private static function parseStatusFromOutput(?string $output): string
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

    /**
     * @return string|null
     */
    public function getRawStatus(): ?string
    {
        return $this->rawStatus;
    }
}
