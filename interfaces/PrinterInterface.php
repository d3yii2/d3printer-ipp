<?php

namespace d3yii2\d3printeripp\interfaces;

/**
 * Common interface for all printer implementations
 */
interface PrinterInterface
{
    public function connect(): bool;
    public function disconnect(): void;
    public function getStatus(): array;
    public function getSuppliesStatus(): array;
    public function getSystemInfo(): array;
    public function printJob(string $document, array $options = []): array;
    public function getJobs(): array;
    public function cancelJob(int $jobId): bool;
    public function isOnline(): bool;
}
