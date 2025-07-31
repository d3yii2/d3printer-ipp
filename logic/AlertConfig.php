<?php

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printer\models\AlertSettings as AlertSettingsModel;
use d3yii2\d3printeripp\logic\PrinterConfig;

/**
 * Class AlertSettings
 * @package d3yii2\d3printer\logic\settings
 */
class AlertConfig
{
    protected PrinterConfig $printerConfig;

    protected int $cartridgeMinValue;

    protected int $drumMinValue;

    protected string $paperSize;

    protected string $paperType;

    protected string $printOrientation;

    protected string $sleepAfter;

    protected string $emailFrom;

    protected ?string $emailTo = null;

    protected string $emailSubject;

    protected string $emailTemplate;

    /**
     * AlertSettings constructor.
     * @param string $addKey
     */
    public function __construct(PrinterConfig $printerConfig)
    {
        $this->printerConfig = $printerConfig;

        $alertSettings = $printerConfig->getAlertSettings() ?? [];

        $this->cartridgeMinValue = $alertSetttings['cartridgeMinValue'] ?? 10;
        $this->drumMinValue = $alertSetttings['drumMinValue'] ?? 10;
        $this->paperSize = $alertSetttings['paperSize'] ?? 'A5';
        $this->paperType = $alertSetttings['paperType'] ?? '27';
        $this->sleepAfter = $alertSetttings['sleepAfter'] ?? '15';
        $this->printOrientation = $alertSetttings['printOrientation'] ?? 'Portrait';

        $this->emailFrom = $alertSetttings['emailFrom'] ?? 'system@localhost';
        $this->emailTo = $alertSetttings['emailTo'] ?? null;
        $this->emailSubject = $alertSetttings['emailSubject'] ?? 'Printer ' . $printerConfig->getName() .  ' Alert';
        $this->emailTemplate = $alertSetttings['emailTemplate'] ?? 'This is alert about the Printer %s ';
    }

    public function getPaperSize(): string
    {
        return $this->paperSize;
    }

    public function getPaperType(): string
    {
        return $this->paperType;
    }

    public function getSleepAfter(): string
    {
        return $this->sleepAfter;
    }

    public function getPrintOrientation(): string
    {
        return $this->printOrientation;
    }


    /**
     * @return string
     */
    public function getCartridgeMinValue(): string
    {
        return $this->cartridgeMinValue;
    }
    
    /**
     * @return string
     */
    public function getDrumMinValue(): string
    {
        return $this->drumMinValue;
    }
    
    /**
     * @return strings
     */
    public function getEmailFrom(): string
    {
        return $this->emailFrom;
    }
    
    /**
     * @return string
     */
    public function getEmailTo(): array
    {
        return $this->emailTo;
    }
    
    /**
     * @return string
     */
    public function getEmailSubject(): string
    {
        return $this->emailSubject;
    }
}
