<?php
namespace d3yii2\d3printeripp\components;



/**
 * HP Printer specific implementation
 */
class HPPrinter extends BasePrinter
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

    private function processhpOptions(array $options = []): array
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

    public function getConfigPanel(): array
    {
        $panel = parent::getConfigPanel();
        return [
            'printerName' => $panel['printerName']??$this->name,
            'printerAccessUrl' => 'https://'.$this->host,
            'status' => $panel['status'],
            'cartridge' => $panel['cartridge'],
            'ip' => $this->host,
        ];
    }
}
