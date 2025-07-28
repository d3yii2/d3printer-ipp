<?php

namespace d3yii2\d3printeripp\logic;

/**
 * Configuration class for printer settings
 */
class PrinterConfig
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $pincode;
    private $encryption;
    private $timeout;
    private $printerType;
    private $additionalSettings;

    public function __construct(array $config)
    {
        $this->host = $config['host'] ?? null;
        $this->port = $config['port'] ?? 631;
        $this->username = $config['username'] ?? null;
        $this->password = $config['password'] ?? null;
        $this->pincode = $config['pincode'] ?? null;
        $this->encryption = $config['encryption'] ?? false;
        $this->timeout = $config['timeout'] ?? 30;
        $this->printerType = $config['type'] ?? 'generic';
        $this->additionalSettings = $config['additional'] ?? [];
    }

    // Getters
    public function getHost(): ?string { return $this->host; }
    public function getPort(): int { return $this->port; }
    public function getUsername(): ?string { return $this->username; }
    public function getPassword(): ?string { return $this->password; }
    public function getPincode(): ?string { return $this->pincode; }
    public function getEncryption(): bool { return $this->encryption; }
    public function setTimeout(): int { return $this->timeout; }
    public function getPrinterType(): string { return $this->printerType; }
    public function getAdditionalSettings(): array { return $this->additionalSettings; }
}
