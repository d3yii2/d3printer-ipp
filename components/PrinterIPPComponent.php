<?php

namespace d3yii2\d3printeripp\components;
use d3yii2\d3printeripp\interfaces\PrinterInterface;
use d3yii2\d3printeripp\logic\BasePrinter;
use d3yii2\d3printeripp\logic\PrinterConfig;
use d3yii2\d3printeripp\logic\PrinterData;
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
    public $printers = [];
    private $instances = [];

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

    public function addPrinter(array $config): void
    {
        $printerConfig = new PrinterConfig($config);
        $printer = PrinterFactory::create($printerConfig);
        $this->instances[$config['slug']] = $printer;
    }

    public function getPrinter(string $slug): ?PrinterInterface
    {
        return $this->instances[$slug] ?? null;
    }

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

    public function getStatus(string $printerSlug): array
    {
        $printer = $this->validatePrinterExists($printerSlug);

        return $this->executeWithErrorHandling(
            fn() => $printer->getFullStatus(),
            $this->createErrorStatus('Failed to get printer status')
        );
    }

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
    public function getSupportedPrinterTypes(): array
    {
        return PrinterFactory::getSupportedTypes();
    }

    /**
     * Register new printer type
     */
    public function registerPrinterType(string $type, string $className): void
    {
        PrinterFactory::registerPrinterType($type, $className);
    }

    private function validatePrinterExists(string $printerSlug): PrinterInterface
    {
        $printer = $this->instances[$printerSlug] ?? null;

        if (!$printer instanceof PrinterInterface) {
            throw new Exception('Printer: ' . $printerSlug . ' is not set');
        }

        return $printer;
    }

    private function executeWithErrorHandling(callable $operation, array $errorResponse): array
    {
        try {
            return $operation();
        } catch (\Exception $e) {
            return array_merge($errorResponse, [self::STATUS_ERROR => $e->getMessage()]);
        }
    }

    private function createErrorStatus(string $defaultMessage): array
    {
        return [
            self::STATUS_ALIVE => false,
            self::STATUS_LAST_CHECK => date('Y-m-d H:i:s')
        ];
    }
}

// ===================================================================
// USAGE EXAMPLES
// ===================================================================
/**
 * Example Controller showing how to use the Printer Manager
 */
class PrinterController extends \yii\web\Controller
{
    /**
     * Print a document
     */
    public function actionPrint()
    {
        try {
            // Get the printer manager component
            $printerManager = \Yii::$app->printerManager;

            // Example document content (could be PDF, PostScript, etc.)
            $document = file_get_contents('/path/to/document.pdf');

            // Print options
            $options = [
                'job-name' => 'Test Document',
                'copies' => 2,
                'media' => 'iso_a4_210x297mm',
                'sides' => 'two-sided-long-edge'
            ];

            // Print to specific printer
            $result = $printerManager->print('office_hp', $document, $options);

            if ($result['success']) {
                return $this->asJson([
                    'status' => 'success',
                    'job_id' => $result['job-id'],
                    'message' => 'Document sent to printer successfully'
                ]);
            } else {
                return $this->asJson([
                    'status' => 'error',
                    'message' => 'Failed to print document'
                ]);
            }

        } catch (\Exception $e) {
            return $this->asJson([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get printer health status
     */
    public function actionHealth(string $slug)
    {
        $printerManager = \Yii::$app->printerManager;
        $health = $printerManager->getHealthStatus($slug);

        return $this->asJson($health);
    }

    /**
     * Get specific printer status
     */
    public function actionStatus($slug)
    {
        $printerManager = \Yii::$app->printerManager;
        $printer = $printerManager->getPrinter($slug);

        if (!$printer) {
            throw new \yii\web\NotFoundHttpException("Printer not found");
        }

        try {
            $status = [
                'online' => $printer->isOnline(),
                'status' => $printer->getStatus(),
                'supplies' => $printer->getSuppliesStatus(),
                'system_info' => $printer->getSystemInfo(),
                'jobs' => $printer->getJobs()
            ];

            return $this->asJson($status);

        } catch (\Exception $e) {
            return $this->asJson([
                'error' => $e->getMessage(),
                'online' => false
            ]);
        }
    }

    /**
     * Cancel a print job
     */
    public function actionCancelJob($slug, $jobId)
    {
        $printerManager = \Yii::$app->printerManager;
        $printer = $printerManager->getPrinter($slug);

        if (!$printer) {
            throw new \yii\web\NotFoundHttpException("Printer not found");
        }

        $success = $printer->cancelJob((int)$jobId);

        return $this->asJson([
            'success' => $success,
            'message' => $success ? 'Job cancelled successfully' : 'Failed to cancel job'
        ]);
    }
}