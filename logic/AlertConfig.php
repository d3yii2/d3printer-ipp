<?php

namespace d3yii2\d3printeripp\logic;

use d3yii2\d3printer\models\AlertSettings as AlertSettingsModel;
use d3yii2\d3printeripp\logic\PrinterConfig;

/**
 * Class AlertConfig
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
     * AlertConfig constructor.
     * @param PrinterConfig $printerConfig
     */
    public function __construct(PrinterConfig $printerConfig)
    {
        $this->printerConfig = $printerConfig;
        $this->initializeSettings();
    }

    /**
     * Initialize alert settings with default values
     */
    private function initializeSettings(): void
    {
        $alertSettings = $this->printerConfig->getAlertSettings() ?? [];

        $this->cartridgeMinValue = $alertSettings['cartridgeMinValue'] ?? 10;
        $this->drumMinValue = $alertSettings['drumMinValue'] ?? 10;
        $this->paperSize = $alertSettings['paperSize'] ?? 'A5';
        $this->paperType = $alertSettings['paperType'] ?? '27';
        $this->sleepAfter = $alertSettings['sleepAfter'] ?? '15';
        $this->printOrientation = $alertSettings['printOrientation'] ?? 'Portrait';
        $this->emailFrom = $alertSettings['emailFrom'] ?? 'system@localhost';
        $this->emailTo = $alertSettings['emailTo'] ?? null;
        $this->emailSubject = $alertSettings['emailSubject'] ?? 'Printer ' . $this->printerConfig->getName() . ' Alert';
        $this->emailTemplate = $alertSettings['emailTemplate'] ?? 'This is alert about the Printer %s ';
    }

    public function getDocumentSize(): string
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
     * @return int
     */
    public function getCartridgeMinValue(): int
    {
        return $this->cartridgeMinValue;
    }

    /**
     * @return int
     */
    public function getDrumMinValue(): int
    {
        return $this->drumMinValue;
    }

    /**
     * @return string
     */
    public function getEmailFrom(): string
    {
        return $this->emailFrom;
    }

    /**
     * @return string|null
     */
    public function getEmailTo(): ?string
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