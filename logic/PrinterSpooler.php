<?php
declare(strict_types=1);

namespace d3yii2\d3printeripp\logic;

use d3system\helpers\D3FileHelper;
use d3yii2\d3printeripp\interfaces\StatusInterface;
use Random\RandomException;
use yii\base\Exception;

/**
 * work only on windows
 * load html page, convert ot PHP and send to windows printer
 *
 * Class Printer
 */
class PrinterSpooler implements StatusInterface
{
    private const DEFAULT_BASE_DIRECTORY = 'd3printeripp';
    private const SPOOL_DIRECTORY_PREFIX = 'spool_';
    private const DEAD_FILE_PREFIX = 'dead_';
    private const DEAD_FILE_EXTENSION = '.txt';
    private const DEAD_FILE_CONTENT = '1';
    private const TIMESTAMP_FORMAT = 'YmdHis';
    private const RANDOM_RANGE_MAX = 999;

    protected PrinterConfig $printerConfig;
    protected string $baseDirectory = self::DEFAULT_BASE_DIRECTORY;

    private array $errors = [];

    /**
     * @param PrinterConfig $config
     */
    public function __construct(PrinterConfig $config)
    {
        $this->printerConfig = $config;
    }

    /**
     * @return array{path: string, filesCount: int, deadFileExists: string}
     * @throws Exception
     */
    public function getStatus(): array
    {
        return [
            'path' => $this->getSpoolDirectory(),
            'filesCount' => $this->getSpoolFilesCount(),
            'deadFileExists' => $this->deadFileExists() ? ValueFormatter::YES : ValueFormatter::NO,
            'errors' => $this->getErrors()
        ];
    }

    /**
     * @throws Exception
     */
    public function printToSpoolDirectory(string $filepath, int $copies = 1): bool
    {
        if (!file_exists($filepath)) {
            throw new Exception("Source file does not exist: $filepath");
        }

        $spoolDirectoryPath = $this->getSpoolDirectoryPath();
        $pathInfo = pathinfo($filepath);

        for ($i = 1; $i <= $copies; $i++) {
            $destinationFile = $spoolDirectoryPath . '/' . $pathInfo['filename'] . $i . '.' . $pathInfo['extension'];
            if (!copy($filepath, $destinationFile)) {
                
                $error = "Failed to copy file to spool directory: $destinationFile";
                
                $this->errors[] = $error;
                
                throw new Exception($error);
            }
        }

        return true;
    }

    /**
     * @throws Exception|RandomException
     */
    public function saveToSpoolDirectory(string $fileData, int $copies = 1): bool
    {
        $spoolDirectoryPath = $this->getSpoolDirectoryPath();
        $timestamp = date(self::TIMESTAMP_FORMAT) . '_' . random_int(0, self::RANDOM_RANGE_MAX);

        for ($i = 1; $i <= $copies; $i++) {
            $destinationFile = $spoolDirectoryPath . '/' . $timestamp . '_' . $i . self::DEAD_FILE_EXTENSION;
            
            if (!file_put_contents($destinationFile, $fileData)) {
                
                $this->errors[] = "Failed to save file to spool directory: $destinationFile";
                
                throw new Exception($error);
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getSpoolDirectory(): string
    {
        return $this->baseDirectory . '/' . self::SPOOL_DIRECTORY_PREFIX . $this->printerConfig->getSlug();
    }

    /**
     * @throws Exception
     */
    private function getSpoolDirectoryPath(): string
    {
        return D3FileHelper::getRuntimeDirectoryPath($this->getSpoolDirectory());
    }

    /**
     * @throws Exception
     */
    public function getSpoolDirectoryFiles(): array
    {
        $files = D3FileHelper::getDirectoryFiles($this->getSpoolDirectory());
        return $files ?: [];
    }

    /**
     * @throws Exception
     */
    public function getSpoolFilesCount(): int
    {
        return count($this->getSpoolDirectoryFiles());
    }

    /**
     * @return string
     */
    private function getDeadFileName(): string
    {
        return self::DEAD_FILE_PREFIX . $this->printerConfig->getSlug() . self::DEAD_FILE_EXTENSION;
    }

    /**
     * @throws Exception
     */
    public function createDeadFile(): void
    {
        D3FileHelper::filePutContentInRuntime($this->baseDirectory, $this->getDeadFileName(), self::DEAD_FILE_CONTENT);
    }

    /**
     * @throws Exception
     */
    public function unlinkDeadFile(): void
    {
        D3FileHelper::fileUnlinkInRuntime($this->baseDirectory, $this->getDeadFileName());
    }

    /**
     * @throws Exception
     */
    public function deadFileExists(): bool
    {
        return D3FileHelper::fileExist($this->baseDirectory, $this->getDeadFileName());
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

