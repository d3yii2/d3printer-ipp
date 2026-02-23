<?php
declare(strict_types=1);

namespace d3yii2\d3printeripp\commands;

use d3system\commands\D3CommandController;
use d3yii2\d3printeripp\components\AlertConfig;
use d3yii2\d3printeripp\components\BasePrinter;
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
     * get and output printer status defined by AlertConfig
     * use for:
     *  - yii printeripp/status ippTest
     *      getting printer status info defined in alert configuration
     *  - yii printeripp/status ippTest 0 1
     *      for checking an email alert message must be sent
     *  - yii printeripp/status ippTest 1
     *      for printer monitoring and alert message sending
     * @param string $printerComponentName - component class \d3yii2\d3printeripp\components\HPPrinter
     * @param bool $chackAndSendAlertMessage - check and send an alert message if printer status has changed
     * @param bool $sendAlertMessage - send a test alert message
     * @return int
     * @throws AuthenticationError
     * @throws Exception
     * @throws HTTPError
     * @throws InvalidConfigException
     */
    public function actionStatus(
        string $printerComponentName,
        bool $chackAndSendAlertMessage = false,
        bool $sendAlertMessage = false
    ): int
    {
        if (!Yii::$app->has($printerComponentName)) {
            $this->out('Not found printer component with slug: "' . $printerComponentName . '"');
            return ExitCode::CONFIG;
        }
        /** @var BasePrinter $printer */
        $printer = Yii::$app->get($printerComponentName);
        $this->out('Printer: ' . $printer->name);

        if ($chackAndSendAlertMessage) {
            $prevAlert = $printer->getStatusFromCache();
        }

        /** @var AlertConfig $alert */
        $alert = $printer->getStatusFromPrinter();
        if ($chackAndSendAlertMessage && $alert->warningMustBeSent( $prevAlert)) {
            $printer->sendAlertEmail($alert);
        } elseif ($sendAlertMessage) {
            $printer->sendAlertEmail($alert);
        }

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
        /** @var BasePrinter $printer */
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
    public function actionTestPrint(string $printerComponentName): void
    {
        // Create a simple test document (PostScript)
        $testDocument = "%!PS-Adobe-3.0\n";
        $testDocument .= "72 720 moveto\n";
        $testDocument .= "/Times-Roman findfont 24 scalefont setfont\n";
        $testDocument .= '(Test Print - ' . date('Y-m-d H:i:s') . ") show\n";
        $testDocument .= "showpage\n";

        /** @var BasePrinter $printer */
        $printer = Yii::$app->get($printerComponentName);

        if (!$printer) {
            throw new Exception('Printer: ' . $printerComponentName . ' is not configured in App!');
        }
        $this->out('Printer: ' . $printer->name);

        $result = $printer->getUncompletedJobs();
        echo 'Uncompleted :' . VarDumper::dumpAsString($result);

        $result = $printer->printContent($testDocument);
        $this->stdout("Printer: {$testDocument}\n");
        if ($result->statusCode) {
            $this->out('Status: ' . $result->statusCode->getClass());
        }
        echo 'printed :' . VarDumper::dumpAsString($result);
        $result = $printer->getUncompletedJobs();
        echo 'Uncompleted :' .  VarDumper::dumpAsString($result);
    }
}
