<?php
declare(strict_types=1);

namespace d3yii2\d3printeripp\commands;

use d3system\commands\D3CommandController;
use d3yii2\d3printeripp\components\AlertConfig;
use d3yii2\d3printeripp\components\HPPrinter;
use d3yii2\d3printeripp\types\PrinterAttributes;
use ea\app\components\IppPrinter3002dnAlertConfig;
use obray\ipp\Attribute;
use obray\ipp\exceptions\AuthenticationError;
use obray\ipp\exceptions\HTTPError;
use obray\ipp\types\Collection;
use ReflectionClass;
use ReflectionException;
use yii\base\Exception;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\ExitCode;
use yii\helpers\VarDumper;

/**
 * Example Console Command for printer management
 */
class PrinterCommand extends D3CommandController
{


    /**
     * @param string $printerComponentName
     * @return int
     * @throws AuthenticationError
     * @throws Exception
     * @throws HTTPError
     * @throws InvalidConfigException
     */
    public function actionStatus(string $printerComponentName): int
    {
        if (!Yii::$app->has($printerComponentName)) {
            $this->out('Not found printer component with slug: "' . $printerComponentName . '"');
            return ExitCode::CONFIG;
        }
        /** @var HPPrinter $printer */
        $printer = Yii::$app->get($printerComponentName);
        $this->out('Printer: ' . $printer->name);
        /** @var AlertConfig $alert */
        $alert = $printer->getStatusFromPrinter();
        if ($alert->hasWarning()) {
            echo VarDumper::dump($alert->getWarningMessages());
        }
        if ($alert->hasError()) {
            echo VarDumper::dump($alert->getErrorMessages());
        }
        echo VarDumper::dump($alert->getDisplayList());
        return ExitCode::OK;
    }

    /**
     * print aut all printer attributes
     * @throws InvalidConfigException
     * @throws HTTPError
     * @throws AuthenticationError
     * @throws Exception
     * @throws ReflectionException
     */
    public function actionAttributes(
        string $printerComponentName,
        ?string $filterAttributeName = null
    ): int
    {
        if (!Yii::$app->has($printerComponentName)) {
            $this->out('Not found printer component with slug: "' . $printerComponentName . '"');
            return ExitCode::CONFIG;
        }
        /** @var HPPrinter $printer */
        $printer = Yii::$app->get($printerComponentName);
        $this->out('Printer: ' . $printer->name);

        $attributes = $printer->getAllAttributes();
        foreach ($attributes as $attributeName => $attribute) {
            if (!$filterAttributeName || $filterAttributeName === $attributeName) {
                $this->printAttribute($attribute);
            }
        }
        return ExitCode::OK;
    }

    /**
     * @throws ReflectionException
     *
     */
    private function printAttribute(
        $attribute,
        string $indent = '',
        ?string $key = null
    ): void
    {
        $this->out('');
        if ($key) {
            $this->out($indent . ' key: ' . $key);
        }
        if ($attribute instanceof Attribute ) {
            $attributeObject = $attribute->getAttributeValueClass();
            if ($name = $attribute->getName()) {
                $this->out($indent . $name);
            }
            $this->out($indent . ' class: ' . get_class($attributeObject));
            if (!$attributeObject instanceof Collection) {
                $this->out($indent . ' value: ' . $attribute->getAttributeValue());
            } else {
                $reflection = new ReflectionClass($attributeObject);
                $property = $reflection->getProperty('attributes');
                $property->setAccessible(true);
                foreach ($property->getValue($attributeObject) as $k => $attribute2) {
                    self::printAttribute(
                        $attribute2,
                        $indent . '  ',
                        $k
                    );
                }

            }
        } elseif ($attribute instanceof Collection) {
                $reflection = new ReflectionClass($attribute);
                $property = $reflection->getProperty('attributes');
                $property->setAccessible(true);
                foreach ($property->getValue($attribute) as $k => $attribute2) {
                    self::printAttribute(
                        $attribute2,
                        $indent . '  ',
                        $k
                    );
                }
        } elseif (is_array($attribute)) {
            foreach ($attribute as $key2 => $attribute2) {
                self::printAttribute(
                    $attribute2,
                    $indent . '  ',
                    (string)$key2
                );
            }
        } elseif (method_exists($attribute, 'getValue')) {
            $this->out($indent . ' class: ' . get_class($attribute));
            $this->out($indent . ' value: ' . $attribute->getValue());
        } else {
            $this->out($indent . 'VarDump: ' . VarDumper::dumpAsString($attribute));
        }
    }

    /**
     * Test print to all printers
     * @throws Exception
     */
    public function actionTestPrint(string $slug): void
    {
        // Create a simple test document (PostScript)
        $testDocument = "%!PS-Adobe-3.0\n";
        $testDocument .= "72 720 moveto\n";
        $testDocument .= "/Times-Roman findfont 24 scalefont setfont\n";
        $testDocument .= '(Test Print - ' . date('Y-m-d H:i:s') . ") show\n";
        $testDocument .= "showpage\n";
        
        $options = [
            PrinterAttributes::JOB_NAME => 'Test Print Command',
            PrinterAttributes::COPIES => 2,
            //PrinterAttributes::ORIENTATION_REQUESTED => PrinterAttributeValues::ORIENTATION_LANDSCAPE,
           // PrinterAttributes::MEDIA => PrinterAttributeValues::MEDIA_SIZE_A4,
            // Alternative approaches:
            // 'media-size' => ['x-dimension' => 21000, 'y-dimension' => 29700], // micrometers
            'media-size-name' => 'iso_a4_210x297mm',
        ];

        $printer = isset($this->printerIPP->printers[$slug]);

        if (!$printer) {
            throw new Exception('Printer: ' . $slug . ' is not configured in App!');
        }

        $result = $this->printerIPP->printBySlug($slug, $testDocument, $options);
        
        $this->stdout("Printer: {$slug}\n");
        if (isset($result['success'])) {
            $this->stdout("Success: Job ID {$result['job-id']}\n");
        } else {
            $this->stdout("Failed: {$result['error']}\n");
        }
        $this->stdout("\n");
    }
}
