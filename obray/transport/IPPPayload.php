<?php

namespace d3yii2\d3printeripp\obray\transport;

use d3yii2\d3printeripp\obray\JobAttributes;
use d3yii2\d3printeripp\obray\OperationAttributes;
use d3yii2\d3printeripp\obray\PrinterAttributes;
use Exception;
use obray\ipp\types\Integer;
use obray\ipp\types\StatusCode;
use obray\ipp\types\VersionNumber;

class IPPPayload extends \obray\ipp\transport\IPPPayload
{

    /**
     * @throws Exception
     */
    public function decode($binary): void
    {
        $unpacked = unpack("cMajor/cMinor/nStatusCode/NRequestID", $binary);

        $this->versionNumber = new VersionNumber($unpacked['Major'] . '.' . $unpacked['Minor']);
        $this->statusCode = new StatusCode($unpacked['StatusCode']);
        $this->requestId = new Integer($unpacked['RequestID']);

        $offset = 8;

        // decode operation attributes
        $this->operationAttributes = new OperationAttributes();
        $newTag = $this->operationAttributes->decode($binary, $offset);

        // decode job attributes
        if ($newTag === 0x02){
            $this->jobAttributes = [];
            while($newTag === 0x02){
                $jobAttributes = new JobAttributes();
                $newTag = $jobAttributes->decode($binary, $offset);
                $this->jobAttributes[] = $jobAttributes;
            }
        }

        // decode printer attributes
        if($newTag === 0x04){
            $this->printerAttributes = [];
            while($newTag === 0x04){
                $printerAttributes = new PrinterAttributes();
                $newTag = $printerAttributes->decode($binary, $offset);
                $this->printerAttributes[] = $printerAttributes;
            }
        }
    }

}