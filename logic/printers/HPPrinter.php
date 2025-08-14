<?php
namespace d3yii2\d3printeripp\logic\printers;

use d3yii2\d3printeripp\logic\BasePrinter;
use d3yii2\d3printeripp\interfaces\PrinterInterface;

/**
 * HP Printer specific implementation
 */
class HPPrinter extends BasePrinter implements PrinterInterface
{
    public const SLUG = 'hpPrinter';

    public function getSuppliesStatus(): array
    {
        // HP-specific supply status implementation
        $data = parent::getSuppliesStatus();
        
        // Add HP-specific processing if needed
        return $this->processHPSupplies($data);
    }

    public function printJob(string $document, array $options = []): array
    {
        // HP-specific print job handling
        $hpOptions = $this->processhpOptions($options);
        return parent::printJob($document, $hpOptions);
    }

    private function processhpOptions(array $options = [])
    {
        return $options;
    }

    private function processHPSupplies(array $supplies): array
    {
        // Add HP-specific supply processing logic
        foreach ($supplies as &$supply) {
            // HP printers might have specific color codes or naming conventions
            if (isset($supply['color']) && $supply['color'] === '#000000') {
                $supply['color_name'] = 'Black';
            }
        }
        
        return $supplies;
    }
}
