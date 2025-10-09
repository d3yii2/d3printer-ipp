<?php
namespace d3yii2\d3printeripp\logic\printers;

use d3yii2\d3printeripp\interfaces\PrinterInterface;
use d3yii2\d3printeripp\logic\BasePrinter;

/**
 * Generic IPP Printer implementation
 */
class GenericIPPPrinter extends BasePrinter implements PrinterInterface
{
    public const SLUG = 'genericPrinter';

    // Generic implementation uses base class methods
    // Can be extended for specific printer behaviors
}
