<?php

namespace d3yii2\d3printeripp\logic;

use obray\ipp\exceptions\AuthenticationError;
use obray\ipp\exceptions\HTTPError;
use obray\ipp\OperationAttributes;
use d3yii2\d3printeripp\interfaces\PrinterInterface;
use obray\ipp\transport\IPPPayload;
use obray\ipp\types\Integer;
use obray\ipp\types\Operation;
use obray\ipp\types\VersionNumber;

class Request
{
    /**
     * @param PrinterInterface $printer
     * @param string $operation
     * @return IPPPayload
     * @throws AuthenticationError
     * @throws HTTPError
     */
    public static function get(
        PrinterInterface $printer,
        string $operation
    ): IPPPayload
    {
        $operationAttributes = new OperationAttributes();
        $operationAttributes->{'printer-uri'} = $printer->getUri();
        $operationAttributes->{'requesting-user-name'} = $printer->getUsername();

        $payload = new IPPPayload(
            new VersionNumber('1.1'),
            new Operation($operation),
            new Integer(1),
            NULL,
            $operationAttributes
        );
        $encodedPayload = $payload->encode();

        return \obray\ipp\Request::send(
            $printer->getUri(),
            $encodedPayload,
            $printer->getUsername(),
            $printer->getPassword(),
            $printer->getCurlOptions()
        );
    }

//    public static function set(PrinterConfig $config, string $operation, array $curlOptions = []): IPPPayload
//    {
//        //@TODO
//    }

}