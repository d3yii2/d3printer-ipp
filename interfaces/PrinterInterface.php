<?php

namespace d3yii2\d3printeripp\interfaces;


/**
 * Common interface for all printer implementations
 */
interface PrinterInterface
{
    public function getUri(): string;
    public function getUsername(): ?string;
    public function getPassword(): ?string;
    public function getCurlOptions(): ?array;
}
