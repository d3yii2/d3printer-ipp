<?php

namespace d3yii2\d3printeripp\logic;


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
 * Get Printer attributes
 */
class PrinterAttributes
{
    protected ?IPPPrinterAttributes $attributes = null;
    protected ?PrinterConfig $printerConfig;


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

        $responsePatyload = Request::get($this->printerConfig, \obray\ipp\types\Operation::GET_PRINTER_ATTRIBUTES);

        $printerAttributes = $responsePatyload->printerAttributes ?? null;

        if (!empty($printerAttributes[0]) && $printerAttributes[0] instanceof IPPPrinterAttributes) {
            $this->attributes = $printerAttributes[0];

            return $this->attributes;
        }

        throw new Exception('Cannot request Printer attributes');
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
     * @return Attribute
     * @throws Exception
     */
    public function getPrinterState(): Attribute
    {
        return $this->getAttributeValue(PrinterAttributeType::PRINTER_STATE);
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getPrinterOutputTray(): Attribute
    {
        return $this->getAttributeValue(PrinterAttributeType::PRINTER_OUTPUT_TRAY);
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getMarkerLevels(): Attribute
    {
        return $this->getAttributeValue('marker-levels');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getMarkerColors(): Attribute
    {
        return $this->getAttributeValue('marker-colors');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getMarkerNames(): Attribute
    {
        return $this->getAttributeValue('marker-names');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getMarkerTypes(): Attribute
    {
        return $this->getAttributeValue('marker-types');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getPrinterInfo(): Attribute
    {
        return $this->getAttributeValue('printer-info');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getPrinterMakeAndModel(): Attribute
    {
        return $this->getAttributeValue('printer-make-and-model');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getPrinterLocation(): Attribute
    {
        return $this->getAttributeValue('printer-location');
    }


    /**
     * @return Attribute
     * @throws Exception
     */
    public function getDocumentSize(): Attribute
    {
        return $this->getAttributeValue(\d3yii2\d3printeripp\types\PrinterAttributes::MEDIA_SIZE);
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getPrintOrientation(): Attribute
    {
        return $this->getAttributeValue(\d3yii2\d3printeripp\types\PrinterAttributes::ORIENTATION_REQUESTED);
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getDrumLevel(): Attribute
    {
        //@TODO
        return $this->getAttributeValue('');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getDeviceUri(): Attribute
    {
        return $this->getAttributeValue('device-uri');
    }

}