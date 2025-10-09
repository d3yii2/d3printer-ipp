<?php
declare(strict_types=1);

namespace d3yii2\d3printeripp\commands;

use d3yii2\d3printeripp\components\PrinterIPP;
use d3yii2\d3printeripp\types\PrinterAttributes;
use d3yii2\d3printeripp\logic\printers\HPPrinter;
use yii\base\Exception;
use Yii;
use yii\console\Controller;

/**
 * Example Console Command for printer management
 */
class PrinterCommand extends Controller
{
    private PrinterIPP $printerIPP;

    /**
     * {@inheritdoc}
     * @since 2.0.36
     */
    public function init()
    {
        $this->printerIPP = Yii::$app->printerIPP;
    }

    /**
     * Check all printer health
     */
    public function actionHealthAll()
    {
        try {
            $health = $this->printerIPP->getStatusAll(true); // Force refresh

            foreach ($health as $printerName => $status) {
                print_r($status);
                $this->stdout("Printer: $printerName\n");
                $this->stdout("Online: " . ( $status['system']['alive'] === 1  ? 'Yes' :  'No' ) . "\n");

                if (isset($status['supplies'])) {
                    $this->stdout("Supplies:\n");
                    foreach ($status['supplies'] as $supply) {
                        //@TODO
                        //$this->stdout("  - {$supply['name']}: {$supply['level']}% ({$supply['status']})\n");
                    }
                }

                if (isset($status['error'])) {
                    $this->stdout("Error: {$status['error']}\n");
                }

                $this->stdout("\n");
            }
        } catch (Exception $e) {
            Yii::error($e->getTraceAsString());
            $this->stdout("Unexpected error \n");
        }
    }

    public function actionHealth(?string $slug)
    {
        $health = $this->printerIPP->getHealthStatus($slug, true); // Force refresh

        print_r($status);
        $this->stdout("Printer: {$printerName}\n");
        $this->stdout("Online: " . ($status['online'] ? 'Yes' : 'No') . "\n");

        if (isset($status['supplies'])) {
            $this->stdout("Supplies:\n");
            foreach ($status['supplies'] as $supply) {
                $this->stdout("  - {$supply['name']}: {$supply['level']}% ({$supply['status']})\n");
            }
        }

        if (isset($status['error'])) {
            $this->stdout("Error: {$status['error']}\n");
        }

        $this->stdout("\n");
    }

    /**
     * Test print to all printers
     * @throws Exception
     */
    public function actionTestPrint(?string $slug = HPPrinter::SLUG)
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
