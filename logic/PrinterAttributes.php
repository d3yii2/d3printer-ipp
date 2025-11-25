<?php
namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printeripp\interfaces\PrinterInterface;
use d3yii2\d3printeripp\interfaces\StatusInterface;
use obray\ipp\Attribute;
use obray\ipp\exceptions\AuthenticationError;
use obray\ipp\exceptions\HTTPError;
use obray\ipp\types\Operation;
use yii\base\Exception;
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
    protected ?PrinterInterface $printer;

    private array $errors = [];

    /**
     * Constructor method for initializing the PrinterConfig.
     */
    public function __construct(PrinterInterface $printer)
    {
        $this->printer = $printer;
    }

    /**
     * @return IPPPrinterAttributes
     * @throws Exception
     * @throws AuthenticationError
     * @throws HTTPError
     */
    public function getAll(): IPPPrinterAttributes
    {
        if ($this->attributes) {
            return $this->attributes;
        }
        
        $responsePayload = Request::get(
            $this->printer,
            Operation::GET_PRINTER_ATTRIBUTES
        );
        
        $printerAttributes = $responsePayload->printerAttributes ?? null;
        if ($printerAttributes
            &&!empty($printerAttributes[0])
            && $printerAttributes[0] instanceof IPPPrinterAttributes
        ) {
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
     */
    public function getAttributeValue(string $key)
    {
        return $this->getAttribute($key)->getAttributeValue();
    }

    /**
     * Generic method to get string attribute values with error handling
     * @param string $attributeKey
     * @return string
     */
    private function getStringAttributeValue(string $attributeKey): string
    {
       return (string) $this->getAttributeValue($attributeKey);
    }

    /**
     * @return string
     */
    public function getPrinterState(): string
    {
        return $this->getStringAttributeValue(PrinterAttributeType::PRINTER_STATE);
    }

    /**
     * @return string
     */
    public function getPrinterOutputTray(): string
    {
        return $this->getStringAttributeValue(PrinterAttributeType::PRINTER_OUTPUT_TRAY);
    }

    /**
     * @return string
     */
    public function getMarkerLevels(): string
    {
        return $this->getStringAttributeValue(self::ATTRIBUTE_KEYS['marker-levels']);
    }

    /**
     * @return string
     */
    public function getMarkerColors(): string
    {
        return $this->getStringAttributeValue(self::ATTRIBUTE_KEYS['marker-colors']);
    }

    /**
     * @return string
     */
    public function getMarkerNames(): string
    {
        return $this->getStringAttributeValue(self::ATTRIBUTE_KEYS['marker-names']);
    }

    /**
     * @return string
     */
    public function getMarkerTypes(): string
    {
        return $this->getStringAttributeValue(self::ATTRIBUTE_KEYS['marker-types']);
    }

    /**
     * @return string
     */
    public function getPrinterInfo(): string
    {
        return $this->getStringAttributeValue(self::ATTRIBUTE_KEYS['printer-info']);
    }

    /**
     * @return string
     */
    public function getPrinterMakeAndModel(): string
    {
        return $this->getStringAttributeValue(self::ATTRIBUTE_KEYS['printer-make-and-model']);
    }

    /**
     * @return string
     */
    public function getPrinterLocation(): string
    {
        return $this->getStringAttributeValue(self::ATTRIBUTE_KEYS['printer-location']);
    }

    /**
     * @return string
     */
    public function getDocumentSize(): string
    {
        // TODO: Fix incorrect attribute reference
        return '???'; // $this->getStringAttributeValue(PrinterAttributeType::MEDIA_SIZE);
    }

    /**
     * @return string
     */
    public function getPrintOrientation(): string
    {
        return $this->getStringAttributeValue(PrinterAttributeType::ORIENTATION_REQUESTED);
    }

    /**
     * @return string
     */
    public function getDrumLevel(): string
    {
        // TODO: Implement drum level attribute retrieval
        return $this->getStringAttributeValue('');
    }

    /**
     * @throws AuthenticationError
     * @throws Exception
     * @throws HTTPError
     */
    public function getStatus(): array
    {
        $this->getAll();

        return [

        ];
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}