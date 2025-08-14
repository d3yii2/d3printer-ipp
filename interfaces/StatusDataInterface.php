<?php

namespace d3yii2\d3printeripp\interfaces;

/**
 * Common interface for all status types
 */
interface StatusDataInterface
{
    public function buildStats(): array;
}
