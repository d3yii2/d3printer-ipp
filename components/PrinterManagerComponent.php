<?php

namespace d3yii2\d3printeripp\components;

use yii\base\Component;
use yii\base\InvalidConfigException;
use d3yii2\d3printeripp\logic\PrinterManager;
use d3yii2\d3printeripp\logic\PrinterFactory;

/**
 * Yii2 Application Component for Printer Management
 * 
 * Configuration example in config/web.php:
 * 
 * 'components' => [
 *     'printerManager' => [
 *         'class' => 'd3yii2\d3printeripp\components\PrinterManagerComponent',
 *         'printers' => [
 *             'office_hp' => [
 *                 'type' => 'hp',
 *                 'host' => '192.168.1.100',
 *                 'port' => 631,
 *                 'username' => 'admin',
 *                 'password' => 'password123',
 *                 'timeout' => 30
 *             ],
 *             'warehouse_canon' => [
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
class PrinterManagerComponent extends Component
{
    /**
     * @var array Printer configurations
     */
    public $printers = [];

    /**
     * @var PrinterManager
     */
    private $printerManager;

    /**
     * @var bool Auto-connect to all printers on initialization
     */
    public $autoConnect = false;

    /**
     * @var int Health check interval in seconds
     */
    public $healthCheckInterval = 300;

    /**
     * @var array Last health check results
     */
    private $lastHealthCheck = [];

    /**
     * @var int Last health check timestamp
     */
    private $lastHealthCheckTime = 0;

    public function init()
    {
        parent::init();
        
        if (empty($this->printers)) {
            throw new InvalidConfigException('At least one printer must be configured.');
        }
        
        $this->printerManager = new PrinterManager();
        
        // Add all configured printers
        foreach ($this->printers as $name => $config) {
            $this->printerManager->addPrinter($name, $config);
        }
        
        if ($this->autoConnect) {
            $this->connectAll();
        }
    }

    /**
     * Get printer by name
     */
    public function getPrinter(string $name)
    {
        return $this->printerManager->getPrinter($name);
    }

    /**
     * Add new printer at runtime
     */
    public function addPrinter(string $name, array $config): void
    {
        $this->printers[$name] = $config;
        $this->printerManager->addPrinter($name, $config);
    }

    /**
     * Remove printer
     */
    public function removePrinter(string $name): void
    {
        unset($this->printers[$name]);
        $this->printerManager->removePrinter($name);
    }

    /**
     * Get all printer names
     */
    public function getAllPrinters(): array
    {
        return $this->printerManager->getAllPrinters();
    }

    /**
     * Connect to all printers
     */
    public function connectAll(): array
    {
        return $this->printerManager->connectAll();
    }

    /**
     * Disconnect from all printers
     */
    public function disconnectAll(): void
    {
        $this->printerManager->disconnectAll();
    }

    /**
     * Get health status with caching
     */
    public function getHealthStatus(bool $forceRefresh = false): array
    {
        $now = time();
        
        if ($forceRefresh || 
            ($now - $this->lastHealthCheckTime) > $this->healthCheckInterval) {
            
            $this->lastHealthCheck = $this->printerManager->getHealthStatus();
            $this->lastHealthCheckTime = $now;
        }
        
        return $this->lastHealthCheck;
    }

    /**
     * Print document to specific printer
     */
    public function print(string $printerName, string $document, array $options = []): array
    {
        $printer = $this->getPrinter($printerName);
        
        if (!$printer) {
            throw new InvalidConfigException("Printer '{$printerName}' not found.");
        }
        
        return $printer->printJob($document, $options);
    }

    /**
     * Print document to all printers
     */
    public function printToAll(string $document, array $options = []): array
    {
        return $this->printerManager->printToAll($document, $options);
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
    public function actionHealth()
    {
        $printerManager = \Yii::$app->printerManager;
        $health = $printerManager->getHealthStatus();
        
        return $this->asJson($health);
    }

    /**
     * Get specific printer status
     */
    public function actionStatus($printerName)
    {
        $printerManager = \Yii::$app->printerManager;
        $printer = $printerManager->getPrinter($printerName);
        
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
    public function actionCancelJob($printerName, $jobId)
    {
        $printerManager = \Yii::$app->printerManager;
        $printer = $printerManager->getPrinter($printerName);
        
        if (!$printer) {
            throw new \yii\web\NotFoundHttpException("Printer not found");
        }
        
        $success = $printer->cancelJob((int)$jobId);
        
        return $this->asJson([
            'success' => $success,
            'message' => $success ? 'Job cancelled successfully' : 'Failed to cancel job'
        ]);
    }

    /**
     * Add new printer at runtime
     */
    public function actionAddPrinter()
    {
        $request = \Yii::$app->request;
        
        $config = [
            'type' => $request->post('type', 'generic'),
            'host' => $request->post('host'),
            'port' => (int)$request->post('port', 631),
            'username' => $request->post('username'),
            'password' => $request->post('password'),
            'pincode' => $request->post('pincode'),
            'encryption' => (bool)$request->post('encryption', false),
            'timeout' => (int)$request->post('timeout', 30)
        ];
        
        $name = $request->post('name');
        
        try {
            $printerManager = \Yii::$app->printerManager;
            $printerManager->addPrinter($name, $config);
            
            // Test connection
            $printer = $printerManager->getPrinter($name);
            $connected = $printer->connect();
            
            return $this->asJson([
                'success' => true,
                'connected' => $connected,
                'message' => $connected ? 'Printer added and connected' : 'Printer added but connection failed'
            ]);
            
        } catch (\Exception $e) {
            return $this->asJson([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

