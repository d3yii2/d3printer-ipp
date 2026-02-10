# IPP Printer Manager for Yii2

A PHP 7.4+ package for managing IPP printers in Yii2 applications using the nateobray/IPP library.

## Installation

```bash
composer require yourcompany/yii2-ipp-printer-manager
```

## Configuration

In config console add controller:

```php
'controllerMap' => [
        'printeripp' => [
            'class' => 'd3yii2\d3printeripp\commands\PrinterCommand',
        ],
    ],
```

Add the components

```php
    'modules' => [
        'd3printeripp' => [
            'class' => 'd3yii2\d3printeripp\Module',
            /** roles for view dashboard     panel */
            'panelViewRoleNames' => ['D3PrinterViewPanel'],
        ],
    ],
'components' => [
        /** use for printer files spooling */
        'printerSpooler' => [
            'class' => 'd3yii2\d3printer\components\Spooler',
            'baseDirectory' => 'd3printer'
        ],
        /** define alert config for printers. class d3yii2\d3printeripp\components\components */
        'ippAlertConfig' => [
            'class' => 'ea\app\components\IppPrinter3002dnAlertConfig'
        ],
        /** define mailer for printers. */
        'ippPrinterMailer' => [
            'class' => 'd3yii2\d3printeripp\components\Mailer',
            'from' => 'zzzz@zzzz.lv',
            'to' => ['zz@sss.lv'],
        ],
        /** define printer status cache */
        'printerStatusCache' => [
            'class' => 'd3yii2\d3printeripp\logic\cache\PrinterCache',
        ],
        /** define printer component */
        'ippTest' => [
            'class' => '\d3yii2\d3printeripp\components\BasePrinter',
            'printerName' => 'ippTest',
            'name' => 'ippTest',
            'host' => '192.168.88.168',  
            'username' => 'admin',
            'password' => '',
            'pincode' => '77777',
            'timeout' => 30,
            'encryption' => false,
            'spoolerComponentName' => 'printerSpooler',
            'alertConfigComponentName' => 'ippAlertConfig',
            'mailerComponentName' => 'ippPrinterMailer',
        ],
],
```

## Usage command

show printer ippTest status
```shell
php yii printeripp/status ippTest

```

show printer ippTest status
```shell
php yii printeripp/status ippTest 0 1
```

check alert and send one time alert email
Mostly add to crontab
```shell
php yii printeripp/status ippTest 0 1
```


### Dashboard panel
```php
 $config['components']['dashboard']['panels']['notifications'][] = [
        'route' => '/d3printeripp/printer-panel/dashboard',
        'params' => [
            'printerComponentName' => 'ippTest'
        ],
        'tag' => 'div',
        'options' => ['class' => 'col-sm-6 col-md-4 col-lg-3']
    ];
```

### Basic Printing

```php
Yii::$app->ippTest->printToSpoolDirectory($filePath),
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
    $result = $printerIPP->printBySlug('printer_name', $document, $options);
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
    