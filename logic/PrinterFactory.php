<?php

namespace d3yii2\d3printeripp\logic;

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

    public static function create(string $printerType, PrinterConfig $config): PrinterInterface
    {
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
