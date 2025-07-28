<?php

namespace d3yii2\d3printeripp\logic\printers;

/**
 * Main Printer Manager class
 */
class PrinterManager
{
    private $printers = [];
    private $configs = [];

    public function addPrinter(string $name, array $config): void
    {
        $printerConfig = new PrinterConfig($config);
        $this->configs[$name] = $printerConfig;
        
        $printer = PrinterFactory::create(
            $printerConfig->getPrinterType(),
            $printerConfig
        );
        
        $this->printers[$name] = $printer;
    }

    public function getPrinter(string $name): ?PrinterInterface
    {
        return $this->printers[$name] ?? null;
    }

    public function removePrinter(string $name): void
    {
        if (isset($this->printers[$name])) {
            $this->printers[$name]->disconnect();
            unset($this->printers[$name]);
            unset($this->configs[$name]);
        }
    }

    public function getAllPrinters(): array
    {
        return array_keys($this->printers);
    }

    public function getHealthStatus(): array
    {
        $status = [];
        
        foreach ($this->printers as $name => $printer) {
            try {
                $status[$name] = [
                    'online' => $printer->isOnline(),
                    'status' => $printer->getStatus(),
                    'supplies' => $printer->getSuppliesStatus(),
                    'system_info' => $printer->getSystemInfo(),
                    'last_check' => date('Y-m-d H:i:s')
                ];
            } catch (\Exception $e) {
                $status[$name] = [
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
        
        foreach ($this->printers as $name => $printer) {
            try {
                $results[$name] = $printer->printJob($document, $options);
                $results[$name]['success'] = true;
            } catch (\Exception $e) {
                $results[$name] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    public function connectAll(): array
    {
        $results = [];
        
        foreach ($this->printers as $name => $printer) {
            $results[$name] = $printer->connect();
        }
        
        return $results;
    }

    public function disconnectAll(): void
    {
        foreach ($this->printers as $printer) {
            $printer->disconnect();
        }
    }
}