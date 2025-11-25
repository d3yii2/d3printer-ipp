<?php

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\logic\printers\GenericIPPPrinter;

/**
 * Configuration class for printer settings
 */
class PrinterConfig
{
    private const DEFAULT_PORT = 631;
    private const DEFAULT_ENCRYPTION = false;
    private const DEFAULT_TIMEOUT = 2000;
    private const DEFAULT_CACHE_DURATION = 60;

    private string $slug;
    private string $name;
    private ?string $daemonName = null;
    private string $host;
    private int $port;
    private ?string $username = null;
    private ?string $password = null;
    private ?string $pincode = null;
    private bool $encryption = false;
    private int $timeout;
    private int $cacheDuration;
    private string $printerType;
    private array $alertSettings;
    private array $jobAttributes;
    private array $additionalSettings;
    private array $curlOptions;
    private array $gatherStates;
    private string $printerSpooler;
    public array $panel;

    public function __construct(array $config)
    {
        $this->initializeFromConfig($config);
    }

    private function initializeFromConfig(array $config): void
    {
        $this->slug = $config['printerSlug'];
        $this->daemonName = $config['daemonName'] ?? null;
        $this->name = $config['name'] ?? null;
        $this->host = $config['host'] ?? null;
        $this->port = $config['port'] ?? self::DEFAULT_PORT;
        $this->username = $config['username'] ?? 'admin';
        $this->password = $config['password'] ?? '';
        $this->pincode = $config['pincode'] ?? null;
        $this->encryption = $config['encryption'] ?? self::DEFAULT_ENCRYPTION;
        $this->timeout = $config['timeout'] ?? self::DEFAULT_TIMEOUT;
        $this->cacheDuration = $config['cacheDuration'] ?? self::DEFAULT_CACHE_DURATION;
        $this->printerType = $config['type'] ?? GenericIPPPrinter::SLUG;
        $this->alertSettings = $config['alertSettings'] ?? [];
        $this->jobAttributes = $config['jobAttributes'] ?? [];
        $this->curlOptions = $config['curlOptions'] ?? [
            'key' => CURLOPT_TIMEOUT,
            'value' => 20,
        ];
        $this->additionalSettings = $config['additional'] ?? [];
        $this->gatherStates = $config['gatherStates'] ?? [
            'PrinterSystem' => [PrinterSystem::STATUS_UP_DOWN],
            'PrinterSupplies' => [PrinterSupplies::STATUS_MARKER_LEVEL],
        ];
        $this->printerSpooler = $config['printerSpooler'] ?? '';
        $this->panel = $config['panel'] ?? [];
    }

    // Getters
    public function getSlug(): ?string { return $this->slug; }
    public function getName(): ?string { return $this->name; }
    public function getDaemonName(): ?string { return $this->daemonName; }
    public function getHost(): ?string { return $this->host; }
    public function getPort(): int { return $this->port; }
    public function getUsername(): ?string { return $this->username; }
    public function getPassword(): ?string { return $this->password; }
    public function getPincode(): ?string { return $this->pincode; }
    public function getEncryption(): bool { return $this->encryption; }
    public function getTimeout(): int { return $this->timeout; }
    public function getCacheDuration(): int { return $this->cacheDuration; }
    public function getPrinterType(): string { return $this->printerType; }
    public function getAlertSettings(): array { return $this->alertSettings; }
    public function getJobAttributes(): array { return $this->jobAttributes; }
    public function getCurlOptions(): array { return $this->curlOptions; }
    public function getAdditionalSettings(): array { return $this->additionalSettings; }
    public function getGatherStates(): array { return $this->gatherStates; }

    public function getUri(): string
    {
        return 'ipp://' . $this->host . ':' . $this->port;
    }
}
