<?php

namespace d3yii2\d3printeripp\logic;


use Obray\IPP\IPP;
use d3yii2\d3printeripp\interfaces\PrinterInterface;

/**
 * Base abstract class for printer implementations
 */
abstract class BasePrinter implements PrinterInterface
{
    protected $config;
    protected $ipp;
    protected $connected = false;
    protected $lastError = null;

    public function __construct(PrinterConfig $config)
    {
        $this->config = $config;
        $this->initializeIPP();
    }

    protected function initializeIPP(): void
    {
        $this->ipp = new IPP();
        $this->ipp->setHost($this->config->getHost());
        $this->ipp->setPort($this->config->getPort());
        
        if ($this->config->getUsername()) {
            $this->ipp->setUsername($this->config->getUsername());
        }
        
        if ($this->config->getPassword()) {
            $this->ipp->setPassword($this->config->getPassword());
        }
        
        $this->ipp->setTimeout($this->config->getTimeout());
    }

    public function connect(): bool
    {
        try {
            // Perform connection test with printer attributes request
            $this->ipp->setOperationId(IPP::GET_PRINTER_ATTRIBUTES);
            $response = $this->ipp->request();
            
            if ($response && !empty($response['printer-attributes'])) {
                $this->connected = true;
                return true;
            }
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
        }
        
        $this->connected = false;
        return false;
    }

    public function disconnect(): void
    {
        $this->connected = false;
        // IPP connections are stateless, so no explicit disconnect needed
    }

    public function isOnline(): bool
    {
        if (!$this->connected) {
            return $this->connect();
        }
        
        try {
            $status = $this->getStatus();
            return isset($status['printer-state']) && 
                   $status['printer-state'] !== 'stopped';
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getStatus(): array
    {
        $this->ipp->setOperationId(IPP::GET_PRINTER_ATTRIBUTES);
        $this->ipp->addAttribute('requested-attributes', 'printer-state');
        $this->ipp->addAttribute('requested-attributes', 'printer-state-reasons');
        $this->ipp->addAttribute('requested-attributes', 'printer-state-message');
        
        $response = $this->ipp->request();
        
        return $response['printer-attributes'] ?? [];
    }

    public function getSuppliesStatus(): array
    {
        $this->ipp->setOperationId(IPP::GET_PRINTER_ATTRIBUTES);
        $this->ipp->addAttribute('requested-attributes', 'marker-levels');
        $this->ipp->addAttribute('requested-attributes', 'marker-colors');
        $this->ipp->addAttribute('requested-attributes', 'marker-names');
        $this->ipp->addAttribute('requested-attributes', 'marker-types');
        
        $response = $this->ipp->request();
        
        return $this->formatSuppliesData($response['printer-attributes'] ?? []);
    }

    public function getSystemInfo(): array
    {
        $this->ipp->setOperationId(IPP::GET_PRINTER_ATTRIBUTES);
        $this->ipp->addAttribute('requested-attributes', 'printer-info');
        $this->ipp->addAttribute('requested-attributes', 'printer-make-and-model');
        $this->ipp->addAttribute('requested-attributes', 'printer-location');
        $this->ipp->addAttribute('requested-attributes', 'device-uri');
        
        $response = $this->ipp->request();
        
        return $response['printer-attributes'] ?? [];
    }

    public function printJob(string $document, array $options = []): array
    {
        $this->ipp->setOperationId(IPP::PRINT_JOB);
        $this->ipp->setData($document);
        
        // Set job attributes
        if (isset($options['job-name'])) {
            $this->ipp->addAttribute('job-name', $options['job-name']);
        }
        
        if (isset($options['copies'])) {
            $this->ipp->addAttribute('copies', (int)$options['copies']);
        }
        
        // Add custom options
        foreach ($options as $key => $value) {
            if (!in_array($key, ['job-name', 'copies'])) {
                $this->ipp->addAttribute($key, $value);
            }
        }
        
        $response = $this->ipp->request();
        
        return [
            'job-id' => $response['job-attributes']['job-id'] ?? null,
            'job-uri' => $response['job-attributes']['job-uri'] ?? null,
            'job-state' => $response['job-attributes']['job-state'] ?? null,
            'success' => isset($response['job-attributes']['job-id'])
        ];
    }

    public function getJobs(): array
    {
        $this->ipp->setOperationId(IPP::GET_JOBS);
        $this->ipp->addAttribute('requested-attributes', 'job-id');
        $this->ipp->addAttribute('requested-attributes', 'job-name');
        $this->ipp->addAttribute('requested-attributes', 'job-state');
        $this->ipp->addAttribute('requested-attributes', 'job-state-reasons');
        
        $response = $this->ipp->request();
        
        return $response['job-attributes'] ?? [];
    }

    public function cancelJob(int $jobId): bool
    {
        $this->ipp->setOperationId(IPP::CANCEL_JOB);
        $this->ipp->addAttribute('job-id', $jobId);
        
        $response = $this->ipp->request();
        
        return isset($response['operation-attributes']['status-code']) &&
               $response['operation-attributes']['status-code'] === IPP::SUCCESSFUL_OK;
    }

    protected function formatSuppliesData(array $data): array
    {
        $supplies = [];
        
        if (isset($data['marker-levels']) && isset($data['marker-names'])) {
            $levels = $data['marker-levels'];
            $names = $data['marker-names'];
            $colors = $data['marker-colors'] ?? [];
            $types = $data['marker-types'] ?? [];
            
            for ($i = 0; $i < count($names); $i++) {
                $supplies[] = [
                    'name' => $names[$i] ?? 'Unknown',
                    'level' => $levels[$i] ?? -1,
                    'color' => $colors[$i] ?? null,
                    'type' => $types[$i] ?? null,
                    'status' => $this->getSupplyStatus($levels[$i] ?? -1)
                ];
            }
        }
        
        return $supplies;
    }

    protected function getSupplyStatus(int $level): string
    {
        if ($level === -1) return 'unknown';
        if ($level === -2) return 'unknown';
        if ($level === -3) return 'unknown';
        if ($level <= 10) return 'low';
        if ($level <= 25) return 'medium';
        return 'ok';
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}