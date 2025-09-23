<?php
declare(strict_types=1);

namespace d3yii2\d3printeripp\components;
use d3yii2\d3printeripp\interfaces\PrinterInterface;
use d3yii2\d3printeripp\logic\PrinterConfig;
use d3yii2\d3printeripp\logic\PrinterData;
use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use d3yii2\d3printeripp\logic\PrinterManager;
use d3yii2\d3printeripp\logic\PrinterFactory;

/**
 * Yii2 Application Component for Printer Management
 *
 * Configuration example in config/web.php:
 *
 * 'components' => [
 *     'printerIPP' => [
 *         'class' => 'd3yii2\d3printeripp\components\PrinterIPPComponent',
 *         'printers' => [
 *             'office_hp' => [
 *                 'name' => 'Office HP printer',
 *                 'type' => 'hp',
 *                 'host' => '192.168.1.100',
 *                 'port' => 631,
 *                 'username' => 'admin',
 *                 'password' => 'password123',
 *                 'timeout' => 30
 *             ],
 *             'warehouse_canon' => [
 *                 'name' => 'Warehouse printer',
 *                 'type' => 'canon',
 *                 'host' => '192.168.1.101',
 *                 'port' => 631,
 *                 'pincode' => '1234',
 *                 'encryption' => true
 *             ]
 *         ]
 *     ]
 * ]
 */
class PrinterIPPComponent extends Component
{
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';
    private const STATUS_ALIVE = 'alive';
    private const STATUS_LAST_CHECK = 'last_check';

    /**
     * @var array Printer configurations
     */
    public array $printers = [];
    private array $instances = [];

    /**
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object is initialized with the
     * given configuration.
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (empty($this->printers)) {
            throw new InvalidConfigException('At least one printer must be configured.');
        }
        // Add all configured printers
        foreach ($this->printers as $config) {
            $this->addPrinter($config);
        }
    }

    /**
     * @param array $config
     * @return void
     */
    public function addPrinter(array $config): void
    {
        $printerConfig = new PrinterConfig($config);
        $printer = PrinterFactory::create($printerConfig);
        $this->instances[$config['slug']] = $printer;
    }

    /**
     * @param string $slug
     * @return PrinterInterface|null
     */
    public function getPrinter(string $slug): ?PrinterInterface
    {
        return $this->instances[$slug] ?? null;
    }

    /**
     * @param string $slug
     * @return void
     */
    public function removePrinter(string $slug): void
    {
        if (isset($this->instances[$slug])) {
            $this->instances[$slug]->disconnect();
            unset($this->instances[$slug]);
        }
    }

    public function getAllPrinters(): array
    {
        return array_keys($this->instances);
    }

    public function getStatusAll(): array
    {
        $status = [];
        foreach ($this->instances as $slug => $printer) {
            $status[$slug] = $this->executeWithErrorHandling(
                fn() => $this->getStatus($slug),
                $this->createErrorStatus('Failed to get printer status')
            );
        }
        return $status;
    }

    /**
     * @throws Exception
     */
    public function getStatus(string $printerSlug): array
    {
        $printer = $this->validatePrinterExists($printerSlug);

        return $this->executeWithErrorHandling(
            fn() => $printer->getFullStatus(),
            $this->createErrorStatus('Failed to get printer status')
        );
    }

    /**
     * @throws Exception
     */
    public function print(string $printerSlug, string $document, array $options = []): array
    {
        $printer = $this->validatePrinterExists($printerSlug);
        $result = ['slug' => $printerSlug];

        return array_merge($result, $this->executeWithErrorHandling(
            fn() => [
                'response' => $printer->getJobs()->print($document, $options),
                self::STATUS_SUCCESS => true
            ],
            [self::STATUS_SUCCESS => false]
        ));
    }

    public function printToAll(string $document, array $options = []): array
    {
        $results = [];
        foreach ($this->instances as $slug => $printer) {
            $results[$slug] = $this->executeWithErrorHandling(
                fn() => array_merge(
                    $printer->getJobs()->print($document, $options),
                    [self::STATUS_SUCCESS => true]
                ),
                [self::STATUS_SUCCESS => false]
            );
        }
        return $results;
    }

    /**
     * Get registered printer types
     */
    public static function getSupportedPrinterTypes(): array
    {
        return PrinterFactory::getSupportedTypes();
    }

    /**
     * @param string $type
     * @param string $className
     * @return void
     */
    public static function registerPrinterType(string $type, string $className): void
    {
        PrinterFactory::registerPrinterType($type, $className);
    }

    /**
     * @param string $printerSlug
     * @return PrinterInterface
     * @throws Exception
     */
    private function validatePrinterExists(string $printerSlug): PrinterInterface
    {
        $printer = $this->instances[$printerSlug] ?? null;

        if (!$printer instanceof PrinterInterface) {
            throw new Exception('Printer: ' . $printerSlug . ' is not set');
        }

        return $printer;
    }

    /**
     * @param callable $operation
     * @param array $errorResponse
     * @return array
     */
    private function executeWithErrorHandling(callable $operation, array $errorResponse): array
    {
        try {
            return $operation();
        } catch (\Exception $e) {
            return array_merge($errorResponse, [self::STATUS_ERROR => $e->getMessage()]);
        }
    }

    /**
     * @return array
     */
    private function createErrorStatus(): array
    {
        return [
            self::STATUS_ALIVE => false,
            self::STATUS_LAST_CHECK => date('Y-m-d H:i:s')
        ];
    }
}
