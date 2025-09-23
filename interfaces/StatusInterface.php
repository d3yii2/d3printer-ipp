<?php

namespace d3yii2\d3printeripp\interfaces;

/**
 * Common interface for all status types
 */
interface StatusInterface
{
    /**
     * @return array
     */
    public function getStatus(): array;
}
