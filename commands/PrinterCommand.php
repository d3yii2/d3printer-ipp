<?php

namespace d3yii2\d3printeripp\commands;


use d3yii2\d3printeripp\components\PrinterManagerComponent;

/**
 * Example Console Command for printer management
 */
class PrinterCommand extends \yii\console\Controller
{
    private PrinterManagerComponent $printerManager;

    public function init()
    {
        $this->printerManager = \Yii::$app->printerManager;
    }

    /**
     * Check all printer health
     */
    public function actionHealthAll()
    {
        $health = $this->printerManager->getHealthStatus(true); // Force refresh
        
        foreach ($health as $printerName => $status) {
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
    }

    /**
     * Test print to all printers
     */
    public function actionTestPrint()
    {
        // Create a simple test document (PostScript)
        $testDocument = "%!PS-Adobe-3.0\n";
        $testDocument .= "72 720 moveto\n";
        $testDocument .= "/Times-Roman findfont 24 scalefont setfont\n";
        $testDocument .= "(Test Print - " . date('Y-m-d H:i:s') . ") show\n";
        $testDocument .= "showpage\n";
        
        $options = [
            'job-name' => 'Test Print Command'
        ];
        
        $results = $this->printerManager->printToAll($testDocument, $options);
        
        foreach ($results as $printerName => $result) {
            $this->stdout("Printer: {$printerName}\n");
            if ($result['success']) {
                $this->stdout("Success: Job ID {$result['job-id']}\n");
            } else {
                $this->stdout("Failed: {$result['error']}\n");
            }
            $this->stdout("\n");
        }
    }
}