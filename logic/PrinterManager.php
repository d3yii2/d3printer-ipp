<?php

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\interfaces\PrinterInterface;
use yii\base\Exception;

/**
 * Main Printer Manager class
 */
class PrinterManager
{
    private $printers = [];

    public function addPrinter(array $config): void
    {
        $printerConfig = new PrinterConfig($config);

        $printer = PrinterFactory::create($printerConfig);
        
        $this->printers[$config['slug']] = $printer;
    }

    public function getPrinter(string $slug): ?PrinterInterface
    {
        return $this->printers[$slug] ?? null;
    }

    public function removePrinter(string $slug): void
    {
        if (isset($this->printers[$slug])) {
            $this->printers[$slug]->disconnect();
            unset($this->printers[$slug]);
        }
    }

    public function getAllPrinters(): array
    {
        return array_keys($this->printers);
    }

    public function getHealthStatusAll(): array
    {
        $status = [];
        
        foreach ($this->printers as $slug => $printer) {
            try {

                $status[$slug] = [
                    'daemon' => $printer->getData(PrinterData::DATA_DAEMON),
                    'system' => $printer->getData(PrinterData::DATA_SYSTEM),
                    'ftp' => $printer->getData(PrinterData::DATA_FTP),
                    'spooler' => $printer->getData(PrinterData::DATA_SPOOLER),
                    'supplies' => $printer->getData(PrinterData::DATA_SUPPLIES),
                    'last_check' => date('Y-m-d H:i:s')
                ];
            } catch (\Exception $e) {
                $status[$slug] = [
                    'alive' => false,
                    'error' => $e->getMessage(),
                    'last_check' => date('Y-m-d H:i:s')
                ];
            }
        }
        
        return $status;
    }

    public function getHealthStatus(string $printerSlug): array
    {
        $status = [];

        if (empty($this->printers[$printerSlug])) {
            throw new Exception('Printer: ' . $printerSlug . ' is not set');
        }

        try {

            /** @var BasePrinter $printer */
            $printer = $this->printers[$printerSlug];

            $status = [
                'name' => $printer->getConfig()->getName(),
                'daemon' => $printer->getData(PrinterData::DATA_DAEMON),
                'system' => $printer->getData(PrinterData::DATA_SYSTEM),
                'ftp' => $printer->getData(PrinterData::DATA_FTP),
                'spooler' => $printer->getData(PrinterData::DATA_SPOOLER),
                'supplies' => $printer->getData(PrinterData::DATA_SUPPLIES),
                'last_check' => date('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            $status = [
                'alive' => false,
                'error' => $e->getMessage(),
                'last_check' => date('Y-m-d H:i:s')
            ];
        }

        return $status;
    }

    public function print(string $printerSlug, string $document, array $options = []): array
    {
        $printer = $this->printers[$printerSlug] ?? null;

        /**  @var BasePrinter $printer */
        if ( !$printer instanceof PrinterInterface ) {
            throw new Exception('Printer: ' . $printerSlug . ' is not set');
        }

        $result = ['slug' => $printerSlug];

        try {
            $result['response'] = $printer->getJobs()->print($document, $options);
            $result['success'] = true;
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    public function printToAll(string $document, array $options = []): array
    {
        $results = [];

        foreach ($this->printers as $slug => $printer) {
            try {
                /**  @var BasePrinter $printer */
                $results[$slug] = $printer->getJobs()->print($document, $options);
                $results[$slug]['success'] = true;
            } catch (\Exception $e) {
                $results[$slug] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}