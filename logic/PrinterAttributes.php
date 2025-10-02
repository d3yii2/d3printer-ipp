<?php
namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\interfaces\StatusInterface;
use d3yii2\d3printeripp\types\PrinterAttributesTypes;
use obray\ipp\Attribute;
use obray\ipp\enums\PrinterState;
use obray\ipp\Printer as IppPrinterClient;
use d3yii2\d3printeripp\interfaces\PrinterInterface;
use obray\ipp\transport\IPPPayload;
use yii\base\Exception;
use d3yii2\d3printeripp\logic\Request;
use obray\ipp\PrinterAttributes as IPPPrinterAttributes;
use d3yii2\d3printeripp\types\PrinterAttributes as PrinterAttributeType;

/**
 * Class PrinterAttributes
 *
 * Represents and interacts with printer attributes utilizing provided configurations.
 * Supports retrieval of various printer-related data, such as its state, output tray,
 * marker details, and other descriptive metadata.
 */
class PrinterAttributes implements StatusInterface
{
    private const ATTRIBUTE_KEYS = [
        'marker-levels' => 'marker-levels',
        'marker-colors' => 'marker-colors',
        'marker-names' => 'marker-names',
        'marker-types' => 'marker-types',
        'printer-info' => 'printer-info',
        'printer-make-and-model' => 'printer-make-and-model',
        'printer-location' => 'printer-location',
    ];

    protected ?IPPPrinterAttributes $attributes = null;
    protected ?PrinterConfig $printerConfig;

    private array $errors = [];

    /**
     * Constructor method for initializing the PrinterConfig.
     */
    public function __construct(PrinterConfig $config)
    {
        $this->printerConfig = $config;
    }

    /**
     * @return IPPPrinterAttributes
     */
    public function getAll(): IPPPrinterAttributes
    {
        if ($this->attributes) {
            return $this->attributes;
        }
        
        $responsePayload = Request::get(
            $this->printerConfig,
            \obray\ipp\types\Operation::GET_PRINTER_ATTRIBUTES,
            $this->printerConfig->getCurlOptions()
        );
        
        $printerAttributes = null; //$responsePayload->printerAttributes ?? null;
        if (!empty($printerAttributes[0]) && $printerAttributes[0] instanceof IPPPrinterAttributes) {
            $this->attributes = $printerAttributes[0];
            return $this->attributes;
        }
        
        $error = 'Cannot request Printer attributes';
        $this->errors[] = $error;
        
        throw new Exception($error);
    }

    /**
     * @param string $key
     * @return Attribute
     */
    public function getAttribute(string $key): Attribute
    {
        return $this->attributes->{$key};
    }

    /**
     * @param string $key
     * @return mixed
     * @throws Exception
     */
    public function getAttributeValue(string $key)
    {
        $attribute = $this->getAttribute($key);
        return $attribute->getAttributeValue();
    }

    /**
     * Generic method to get string attribute values with error handling
     * @param string $attributeKey
     * @return string
     * @throws Exception
     */
    private function getStringAttributeValue(string $attributeKey): string
    {
       return (string) $this->getAttributeValue($attributeKey);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getPrinterState(): string
    {
        return $this->getStringAttributeValue(PrinterAttributeType::PRINTER_STATE);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getPrinterOutputTray(): string
    {
        return $this->getStringAttributeValue(PrinterAttributeType::PRINTER_OUTPUT_TRAY);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getMarkerLevels(): string
    {
        return $this->getStringAttributeValue(self::ATTRIBUTE_KEYS['marker-levels']);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getMarkerColors(): string
    {
        return $this->getStringAttributeValue(self::ATTRIBUTE_KEYS['marker-colors']);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getMarkerNames(): string
    {
        return $this->getStringAttributeValue(self::ATTRIBUTE_KEYS['marker-names']);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getMarkerTypes(): string
    {
        return $this->getStringAttributeValue(self::ATTRIBUTE_KEYS['marker-types']);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getPrinterInfo(): string
    {
        return $this->getStringAttributeValue(self::ATTRIBUTE_KEYS['printer-info']);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getPrinterMakeAndModel(): string
    {
        return $this->getStringAttributeValue(self::ATTRIBUTE_KEYS['printer-make-and-model']);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getPrinterLocation(): string
    {
        return $this->getStringAttributeValue(self::ATTRIBUTE_KEYS['printer-location']);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getDocumentSize(): string
    {
        // TODO: Fix incorrect attribute reference
        return '???'; // $this->getStringAttributeValue(PrinterAttributeType::MEDIA_SIZE);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getPrintOrientation(): string
    {
        return $this->getStringAttributeValue(PrinterAttributeType::ORIENTATION_REQUESTED);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getDrumLevel(): string
    {
        // TODO: Implement drum level attribute retrieval
        return $this->getStringAttributeValue('');
    }
    
    public function getStatus(): array
    {
        $this->getAll();

        $status = [
            
        ];
        
        return $status;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}