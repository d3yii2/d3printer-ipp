<?php

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\interfaces\PrinterInterface;

/**
 * Main Printer Manager class
 */
class PrinterManager
{
    private $printers = [];
    private $configs = [];

    public function addPrinter(array $config): void
    {
        $printerConfig = new PrinterConfig($config);
        $this->configs[$config['slug']] = $printerConfig;
        
        $printer = PrinterFactory::create(
            $printerConfig
        );
        
        $this->printers[$printerConfig->getSlug()] = $printer;
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
            unset($this->configs[$slug]);
        }
    }

    public function getAllPrinters(): array
    {
        return array_keys($this->printers);
    }

    public function getHealthStatus(): array
    {
        $status = [];
        
        foreach ($this->printers as $slug => $printer) {
            try {
                /** @var BasePrinter $printer */

                $status[$slug] = [
                    'online' => $printer->isOnline(),
                    'status' => $printer->getStatus(),
                    'outputTry' => $printer->getPrinterOutputTray(),
                    'supplies' => $printer->getSuppliesStatus(),
                    'system_info' => $printer->getSystemInfo(),
                    'last_check' => date('Y-m-d H:i:s')
                ];
            } catch (\Exception $e) {
                $status[$slug] = [
                    'online' => false,
                    'error' => $e->getMessage(),
                    'last_check' => date('Y-m-d H:i:s')
                ];
            }
        }
        
        return $status;
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