<?php
declare(strict_types=1);

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\logic\printers\HPPrinter;
use d3yii2\d3printeripp\interfaces\PrinterInterface;
use InvalidArgumentException;

/**
 * Printer Factory for creating printer instances
 */
class PrinterFactory
{
    private static array $printerTypes = [
        //'generic' => GenericIPPPrinter::class,
        //'canon' => CanonPrinter::class,
        'hp' => HPPrinter::class,
    ];

    /**
     * @param PrinterConfig $config
     * @return PrinterInterface
     */
    public static function create(PrinterConfig $config): PrinterInterface
    {
        $printerType = $config->getPrinterType();

        if (!array_key_exists($printerType, self::$printerTypes)) {
            throw new InvalidArgumentException(
                "Printer type '$printerType' is not supported.
                 Supported types: " . implode(', ', self::getSupportedTypes())
            );
        }

        $className = self::$printerTypes[$printerType];
        self::validatePrinterClass($className, $printerType);

        return new $className($config);
    }

    /**
     * @param string $type
     * @param string $className
     * @return void
     */
    public static function registerPrinterType(string $type, string $className): void
    {
        self::validatePrinterClass($className, $type);
        self::$printerTypes[$type] = $className;
    }

    /**
     * @return array
     */
    public static function getSupportedTypes(): array
    {
        return array_keys(self::$printerTypes);
    }

    /**
     * @param string $className
     * @param string $printerType
     * @return void
     */
    private static function validatePrinterClass(string $className, string $printerType): void
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException(
                "Printer class '$className' for type '$printerType' does not exist"
            );
        }

        if (!is_subclass_of($className, PrinterInterface::class)) {
            throw new InvalidArgumentException(
                "Printer class '$className' for type '$printerType' must implement PrinterInterface"
            );
        }
    }
}
