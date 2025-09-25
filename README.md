# IPP Printer Manager for Yii2

A PHP 7.4+ package for managing IPP printers in Yii2 applications using the nateobray/IPP library.

## Installation

```bash
composer require yourcompany/yii2-ipp-printer-manager
```

## Configuration

Add the component to your `config/web.php`:

```php
'components' => [
    'printerIPP' => [
        'class' => 'app\components\PrinterIPP',
        'autoConnect' => false,
        'healthCheckInterval' => 300, // 5 minutes
        'printers' => [
            'office_hp_laser' => [
                'type' => 'hp',
                'host' => '192.168.1.100',
                'port' => 631,
                'username' => 'admin',
                'password' => 'secure_password',
                'timeout' => 30,
                'encryption' => false
            ],
            'warehouse_canon' => [
                'type' => 'canon',
                'host' => '192.168.1.101',
                'port' => 631,
                'pincode' => '1234',
                'encryption' => true,
                'timeout' => 45
            ],
            'reception_generic' => [
                'type' => 'generic',
                'host' => '192.168.1.102',
                'port' => 631,
                'timeout' => 20
            ]
        ]
    ]
],
```

## Usage Examples

### Basic Printing

```php
// In your controller
public function actionPrint()
{
    $printerIPP = \Yii::$app->printerIPP;
    
    // Load document (PDF, PostScript, etc.)
    $document = file_get_contents('/path/to/document.pdf');
    
    // Print options
    $options = [
        'job-name' => 'Invoice #12345',
        'copies' => 1,
        'media' => 'iso_a4_210x297mm',
        'sides' => 'one-sided',
        'print-quality' => 'high'
    ];
    
    try {
        $result = $printerIPP->print('office_hp_laser', $document, $options);
        
        if ($result['success']) {
            return $this->asJson([
                'status' => 'success',
                'job_id' => $result['job-id'],
                'message' => 'Document queued for printing'
            ]);
        }
    } catch (\Exception $e) {
        return $this->asJson([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}
```

### Health Monitoring

```php
// Check all printers health
public function actionHealthDashboard()
{
    $printerIPP = \Yii::$app->printerIPP;
    $health = $printerIPP->getHealthStatus();
    
    $summary = [];
    foreach ($health as $name => $status) {
        $summary[$name] = [
            'online' => $status['online'],
            'supplies' => $this->formatSupplies($status['supplies'] ?? []),
            'last_check' => $status['last_check']
        ];
    }
    
    return $this->render('health', ['printers' => $summary]);
}

private function formatSupplies(array $supplies): array
{
    $formatted = [];
    foreach ($supplies as $supply) {
        $formatted[] = [
            'name' => $supply['name'],
            'level' => $supply['level'],
            'status' => $supply['status'],
            'color' => $supply['color'] ?? 'unknown'
        ];
    }
    return $formatted;
}
```

### Job Management

```php
// Get printer jobs
public function actionJobs($printerName)
{
    $printer = \Yii::$app->printerIPP->getPrinter($printerName);
    
    if (!$printer) {
        throw new NotFoundHttpException('Printer not found');
    }
    
    $jobs = $printer->getJobs();
    
    return $this->asJson([
        'printer' => $printerName,
        'jobs' => $jobs
    ]);
}

// Cancel job
public function actionCancelJob($printerName, $jobId)
{
    $printer = \Yii::$app->printerIPP->getPrinter($printerName);
    $success = $printer->cancelJob((int)$jobId);
    
    return $this->asJson([
        'success' => $success,
        'message' => $success ? 'Job cancelled' : 'Failed to cancel job'
    ]);
}
```

### Dynamic Printer Management

```php
// Add printer at runtime
public function actionAddPrinter()
{
    $config = [
        'type' => 'hp',
        'host' => '192.168.1.200',
        'port' => 631,
        'username' => 'printer_admin',
        'password' => 'printer_pass'
    ];
    
    $printerIPP = \Yii::$app->printerIPP;
    $printerIPP->addPrinter('new_printer', $config);
    
    // Test connection
    $printer = $printerIPP->getPrinter('new_printer');
    $connected = $printer->connect();
    
    return $this->asJson([
        'added' => true,
        'connected' => $connected
    ]);
}
```

## Architecture Overview

The package follows these design patterns:

### 1. **Strategy Pattern**
Different printer types (HP, Canon, Generic) implement the same `PrinterInterface` but with specific behaviors.

### 2. **Factory Pattern**
`PrinterFactory` creates appropriate printer instances based on configuration.

### 3. **Manager Pattern**
`printerIPP` orchestrates multiple printers and provides unified access.

### 4. **Component Pattern**
Yii2 integration through `PrinterIPP` for dependency injection.

## Extending the Package

### Adding New Printer Types

```php
use app\components\printer\BasePrinter;

class EpsonPrinter extends BasePrinter
{
    protected function initializeIPP(): void
    {
        parent::initializeIPP();
        
        // Epson-specific initialization
        // Set specific attributes or connection parameters
    }
    
    public function getSuppliesStatus(): array
    {
        $data = parent::getSuppliesStatus();
        
        // Epson-specific supply processing
        return $this->processEpsonSupplies($data);
    }
    
    private function processEpsonSupplies(array $supplies): array
    {
        // Custom Epson supply level interpretation
        foreach ($supplies as &$supply) {
            if ($supply['type'] === 'ink-cartridge') {
                // Epson ink cartridges might report differently
                $supply['epson_specific_data'] = $this->getEpsonInkData($supply);
            }
        }
        
        return $supplies;
    }
}

// Register the new printer type
PrinterFactory::registerPrinterType('epson', EpsonPrinter::class);
```

### Custom Print Options

```php
class CustomPrinter extends BasePrinter
{
    public function printJob(string $document, array $options = []): array
    {
        // Add custom pre-processing
        $document = $this->preprocessDocument($document, $options);
        
        // Add custom IPP attributes
        if (isset($options['custom_option'])) {
            $this->ipp->addAttribute('custom-attribute', $options['custom_option']);
        }
        
        return parent::printJob($document, $options);
    }
    
    private function preprocessDocument(string $document, array $options): string
    {
        // Custom document processing
        return $document;
    }
}
```

## Console Commands

The package includes console commands for printer management:

```bash
# Check all printer health
php yii printer/health

# Test print to all printers
php yii printer/test-print

# Check specific printer status
php yii printer/status office_hp_laser
```

## Error Handling

The package provides comprehensive error handling:

```php
try {
    $result = $printerIPP->print('printer_name', $document, $options);
} catch (\InvalidArgumentException $e) {
    // Configuration or parameter errors
    \Yii::error("Printer configuration error: " . $e->getMessage());
} catch (\Exception $e) {
    // Network, IPP, or printer errors
    \Yii::error("Printer communication error: " . $e->getMessage());
}
```

## Testing

```php
// Basic printer connectivity test
$printer = \Yii::$app->printerIPP->getPrinter('test_printer');
$isOnline = $printer->isOnline();

// Health check test
$health = \Yii::$app->printerIPP->getHealthStatus(true);
foreach ($health as $name => $status) {
    if (!$status['online']) {
        \Yii::warning("Printer {$name} is offline");
    }
}
```

## Security Considerations

1. **Credentials Storage**: Store printer credentials securely, consider using environment variables:

```php
'printers' => [
    'secure_printer' => [
        'host' => getenv('PRINTER_HOST'),
        'username' => getenv('PRINTER_USERNAME'),
        'password' => getenv('PRINTER_PASSWORD'),
    ]
]
```

2. **Network Security**: Use encryption when available:

```php
'encryption' => true,
'timeout' => 30,
```

3. **Input Validation**: Always validate print options and document content before sending to printers.

## Performance Tips

1. **Connection Pooling**: Set `autoConnect => false` and connect only when needed.
2. **Health Check Caching**: Use the built-in caching with appropriate intervals.
3. **Async Processing**: Consider using Yii2 queues for large print jobs.

## Troubleshooting

### Common Issues

1. **Connection Timeouts**: Increase timeout values for slow networks
2. **Authentication Failures**: Verify credentials and printer settings
3. **IPP Version Compatibility**: Some older printers may need specific IPP versions

### Debug Mode

Enable debug logging in your Yii2 configuration:

```php
'log' => [
    'targets' => [
        [
            'class' => 'yii\log\FileTarget',
            'categories' => ['app\components\printer\*'],
            'logFile' => '@runtime/logs/printer.log',
        ],
    ],
],
```
    