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
    protected static ?IPPPayload $responsePayload = null;


    /**
     * @return IPPPrinterAttributes
     */
    protected static function getAll(PrinterConfig $config): IPPPrinterAttributes
    {
        $printerAttributes = self::$responsePayload->printerAttributes[0] ?? null;

        if ($printerAttributes) {
            return $printerAttributes;
        }

        self::$responsePayload = Request::get($config, \obray\ipp\types\Operation::GET_PRINTER_ATTRIBUTES);

        if (self::$responsePayload) {
            $printerAttributes = self::$responsePayload->printerAttributes ?? null;

            if (!empty($printerAttributes[0]) && $printerAttributes[0] instanceof IPPPrinterAttributes) {
                return $printerAttributes[0];
            }
        }

        throw new Exception('Cannot request Printer attributes');
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public static function getPrinterState(PrinterConfig $config): Attribute
    {
        if (!$attributes = self::getAll($config)) {
            throw new Exception('Cannot get Printer state');
        }

        /** @var Attribute\ $stateAttribute */
        return $attributes->{'printer-state'};
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public static function getPrinterOutputTray(PrinterConfig $config): Attribute
    {
        if (!$attributes = self::getAll($config)) {
            throw new Exception('Cannot get Printer Output Try');
        }

        return $attributes->{'printer-output-tray'};
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public static function getMarkerLevels(PrinterConfig $config): Attribute
    {
        if (!$attributes = self::getAll($config)) {
            throw new Exception('Cannot get Marker levels');
        }

        return $attributes->{'marker-levels'};
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public static function getMarkerColors(PrinterConfig $config): Attribute
    {
        if (!$attributes = self::getAll($config)) {
            throw new Exception('Cannot get Marker colors');
        }

        return $attributes->{'marker-colors'};
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public static function getMarkerNames(PrinterConfig $config): Attribute
    {
        if (!$attributes = self::getAll($config)) {
            throw new Exception('Cannot get Marker names');
        }

        return $attributes->{'marker-names'};
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public static function getMarkerTypes(PrinterConfig $config): Attribute
    {
        if (!$attributes = self::getAll($config)) {
            throw new Exception('Cannot get Marker types');
        }

        return $attributes->{'marker-types'};
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public static function getPrinterInfo(PrinterConfig $config): Attribute
    {
        if (!$attributes = self::getAll($config)) {
            throw new Exception('Cannot get Printer info');
        }

        return $attributes->{'printer-info'};
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public static function getPrinterMakeAndModel(PrinterConfig $config): Attribute
    {
        if (!$attributes = self::getAll($config)) {
            throw new Exception('Cannot get Printer Make and Model');
        }

        return $attributes->{'printer-make-and-model'};
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public static function getPrinterLocation(PrinterConfig $config): Attribute
    {
        if (!$attributes = self::getAll($config)) {
            throw new Exception('Cannot get Printer Location');
        }

        return $attributes->{'printer-location'};
    }

    /**
     * @return Attribute
     * @throws Exception
     */
    public static function getDeviceUri(PrinterConfig $config): Attribute
    {
        if (!$attributes = self::getAll($config)) {
            throw new Exception('Cannot get Device Uri');
        }

        return $attributes->{'device-uri'};
    }

}