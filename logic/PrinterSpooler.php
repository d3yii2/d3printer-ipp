<?php


namespace d3yii2\d3printeripp\logic;


use d3system\helpers\D3FileHelper;
use d3yii2\d3printeripp\logic\PrinterConfig;
use yii\base\Component;
use yii\base\Exception;

/**
 * work only on windows
 * load html page, convert ot PHP and send to windows printer
 *
 * Class Printer
 */
class PrinterSpooler
{
    protected PrinterConfig $printerConfig;


    /**
     * @var string
     */
    public $baseDirectory = 'd3printeripp';


    public function __construct(PrinterConfig $config)
    {
        $this->printerConfig = $config;
    }

    /**
     * @throws \yii\base\Exception
     */
    public function printToSpoolDirectory(string $filepath, int $copies = 1): bool
    {
        if(!file_exists($filepath)){
            throw new Exception('Neeksite fails: ' . $filepath);
        }
        $spoolDirectory = D3FileHelper::getRuntimeDirectoryPath($this->getSpoolDirectory());
        $pi = pathinfo($filepath);
        for ($i = 1; $i <= $copies; $i++) {
            $toFile = $spoolDirectory . '/' . $pi['filename'] . $i . '.' . $pi['extension'];
            if (!copy($filepath, $toFile)) {
                throw new Exception('Can not copy file to ' . $toFile);
            }
        }

        return true;
    }

    /**
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    public function saveToSpoolDirectory(string $fileData, int $copies = 1): bool
    {
        $spoolDirectory = D3FileHelper::getRuntimeDirectoryPath($this->getSpoolDirectory());

        $timestamp = date('YmdHis') . '_' . random_int(0,999);
        for ($i = 1; $i <= $copies; $i++) {
            $toFile = $spoolDirectory . '/' . $timestamp .'_' . $i . '.txt';
            if (!file_put_contents($toFile,$fileData)) {
                throw new Exception('Can not save file ' . $toFile . ' to printer spool directory');
            }
        }
        return true;
    }

    public function getSpoolDirectory(): string
    {
        return $this->baseDirectory  . '/spool_' . $this->printerConfig->getSlug();
    }

    /**
     * @throws \yii\base\Exception
     */
    public function getSpoolDirectoryFiles(): array
    {
        if ($list = D3FileHelper::getDirectoryFiles($this->getSpoolDirectory())) {
            return $list;
        }

        return [];
    }

    /**
     * @throws \yii\base\Exception
     */
    public function getSpoolFilesCount(): int
    {
        return count($this->getSpoolDirectoryFiles());
    }


    private function createDeadFileName(): string
    {
        return 'dead_' . $this->printerConfig->getSlug() . '.txt';
    }

    /**
     * @throws \yii\base\Exception
     */
    public function createDeadFile(): void
    {
        D3FileHelper::filePutContentInRuntime($this->baseDirectory, $this->createDeadFileName(), '1');
    }

    /**
     * @throws \yii\base\Exception
     */
    public function unlinkDeadFile(): void
    {
        D3FileHelper::fileUnlinkInRuntime($this->baseDirectory, $this->createDeadFileName());
    }

    /**
     * @throws \yii\base\Exception
     */
    public function existDeadFile(): bool
    {
        return D3FileHelper::fileExist($this->baseDirectory, $this->createDeadFileName());
    }
}