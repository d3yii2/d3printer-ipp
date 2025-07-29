<?php

namespace d3yii2\d3printeripp\logic;


use d3yii2\d3printeripp\types\PrinterAttributesTypes;
use obray\ipp\Attribute;
use obray\ipp\enums\PrinterState;
use obray\ipp\Printer as IppPrinterClient;
use d3yii2\d3printeripp\interfaces\PrinterInterface;
use obray\ipp\transport\IPPPayload;
use yii\base\Exception;
use obray\ipp\PrinterAttributes;

class Request
{
    /**
     * @param PrinterConfig $config
     * @param string $operation
     * @param array $curlOptions
     * @return IPPPayload
     * @throws \obray\ipp\exceptions\AuthenticationError
     * @throws \obray\ipp\exceptions\HTTPError
     */
    public static function get(PrinterConfig $config, string $operation, array $curlOptions = []): IPPPayload
    {
        $operationAttributes = new \obray\ipp\OperationAttributes();
        $operationAttributes->{'printer-uri'} = $config->getUri();
        $operationAttributes->{'requesting-user-name'} = $config->getUsername();

        $payload = new \obray\ipp\transport\IPPPayload(
            new \obray\ipp\types\VersionNumber('1.1'),
            new \obray\ipp\types\Operation($operation),
            new \obray\ipp\types\Integer(1),
            NULL,
            $operationAttributes
        );
        $encodedPayload = $payload->encode();

        return \obray\ipp\Request::send(
            $config->getUri(),
            $encodedPayload,
            $config->getUsername(),
            $config->getPassword(),
            $curlOptions
        );
    }

    public static function set(PrinterConfig $config, string $operation, array $curlOptions = []): IPPPayload
    {
        //@TODO
    }

}