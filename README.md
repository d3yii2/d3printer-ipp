# IPP Printer Manager for Yii2

A PHP 7.4+ package for managing IPP printers in Yii2 applications using the nateobray/IPP library.

## Installation

to composer add 
```json
    "repositories": [
        { "type": "git", "url": "https://github.com/DBRisinajumi/IPP.git"},
    ]

```

```bash
composer require yourcompany/yii2-ipp-printer-manager
```

## Configuration

In config console add comand:

```php
'controllerMap' => [
        'printeripp' => 'd3yii2\d3printeripp\commands\PrinterCommand',
        'printeripp-spool' => 'd3yii2\d3printeripp\commands\SpoolerCommand',
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
            'class' => 'd3yii2\d3printeripp\components\cache\PrinterCache',
        ],
        /** define printer component */
        'ippTest' => [
            'class' => '\d3yii2\d3printeripp\components\BasePrinter',
            'printerName' => 'ippTest',
            'name' => 'ippTest',
            'host' => '192.168.88.168',
            'port' => 631,
            'username' => 'admin',
            'password' => '',
            'pincode' => '111111',
            'timeout' => 30,
            'encryption' => false,
            'spoolerComponentName' => 'printerSpooler',
            'alertConfigComponentName' => 'ippAlertConfig',
            'mailerComponentName' => 'ippPrinterMailer',
            'cacheComponentName' => 'printerStatusCache',
//            'pageOrientation' => 3, // d3yii2\d3printeripp\types\PrinterAttributeValues::ORIENTATION_PORTRAIT
            'pageOrientation' => 4, // d3yii2\d3printeripp\types\PrinterAttributeValues::LANDSCAPE
//            'pageSize' => 'iso_a4_210x297mm', // d3yii2\d3printeripp\types\PrinterAttributeValues::MEDIA_SIZE_A4
            'pageSize' => 'iso_a5_148x210mm', // d3yii2\d3printeripp\types\PrinterAttributeValues::MEDIA_SIZE_A5
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

spooler printing - create as deamon
```shell
php yii printeripp-spool/index spoolerPrinting
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

### Printer Monitoring

create deamon or add to crontab
```shell
php yii printeripp/status ippTest 0 1
```
    