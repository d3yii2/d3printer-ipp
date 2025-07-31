<?php

namespace d3yii2\d3printeripp\logic;

/**
 * Configuration class for printer settings
 */
class PrinterConfig
{
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
    private string $printerType;
    private array $additionalSettings;

    public function __construct(array $config)
    {
        $this->slug = $config['slug'];
        $this->daemonName = $config['daemonName'] ?? null;
        $this->name = $config['name'] ?? null;
        $this->host = $config['host'] ?? null;
        $this->port = $config['port'] ?? 631;
        $this->username = $config['username'] ?? null;
        $this->password = $config['password'] ?? null;
        $this->pincode = $config['pincode'] ?? null;
        $this->encryption = $config['encryption'] ?? false;
        $this->timeout = $config['timeout'] ?? 20;
        $this->printerType = $config['type'] ?? 'generic';
        $this->alertSettings = $config['alertSettings'] ?? [];
        $this->additionalSettings = $config['additional'] ?? [];
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
    public function getPrinterType(): string { return $this->printerType; }
    public function getAlertSettings(): array { return $this->alertSettings; }
    public function getAdditionalSettings(): array { return $this->additionalSettings; }
    public function getUri(): string
    {
        return 'ipp://' . $this->host . ':' . $this->port;
    }
}
