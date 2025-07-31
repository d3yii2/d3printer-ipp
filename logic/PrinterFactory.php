<?php

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\logic\printers\HPPrinter;
use d3yii2\d3printeripp\interfaces\PrinterInterface;

/**
 * Printer Factory for creating printer instances
 */
class PrinterFactory
{
    private static $printerTypes = [
        //'generic' => GenericIPPPrinter::class,
        'hp' => HPPrinter::class,
        //'canon' => CanonPrinter::class,
    ];

    public static function create(PrinterConfig $config): PrinterInterface
    {
        $printerType = $config->getPrinterType();
        $className = self::$printerTypes[$printerType] ?? self::$printerTypes['generic'];
        
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Printer type '{$printerType}' is not supported");
        }
        
        return new $className($config);
    }

    public static function registerPrinterType(string $type, string $className): void
    {
        if (!is_subclass_of($className, PrinterInterface::class)) {
            throw new \InvalidArgumentException("Class must implement PrinterInterface");
        }
        
        self::$printerTypes[$type] = $className;
    }

    public static function getSupportedTypes(): array
    {
        return array_keys(self::$printerTypes);
    }
}
