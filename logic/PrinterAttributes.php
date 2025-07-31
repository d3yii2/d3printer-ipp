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
        $this->attributes = $this->getAll();
    }

    /**
     * @return IPPPrinterAttributes
     */
    protected function getAll(): IPPPrinterAttributes
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
     * @return mixed
     * @throws Exception
     */
    public function getAttribute(string $key)
    {
        return $this->attributes->{$key};
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getPrinterState(): Attribute
    {
        return $this->getAttribute('printer-state');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getPrinterOutputTray(): Attribute
    {
        return $this->getAttribute('printer-output-tray');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getMarkerLevels(): Attribute
    {
        return $this->getAttribute('marker-levels');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getMarkerColors(): Attribute
    {
        return $this->getAttribute('marker-colors');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getMarkerNames(): Attribute
    {
        return $this->getAttribute('marker-names');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getMarkerTypes(): Attribute
    {
        return $this->getAttribute('marker-types');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getPrinterInfo(): Attribute
    {
        return $this->getAttribute('printer-info');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getPrinterMakeAndModel(): Attribute
    {
        return $this->getAttribute('printer-make-and-model');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getPrinterLocation(): Attribute
    {
        return $this->getAttribute('printer-location');
    }


    /**
     * @return Attribute
     * @throws Exception
     */
    public function getPaperSize(): Attribute
    {
        //@TODO
        return $this->getAttribute('');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getPrintOrientation(): Attribute
    {
        //@TODO
        return $this->getAttribute('');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getDrumLevel(): Attribute
    {
        //@TODO
        return $this->getAttribute('');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public function getDeviceUri(): Attribute
    {
        return $this->getAttribute('device-uri');
    }

}