<?php
declare(strict_types=1);

namespace d3yii2\d3printeripp\components;

use d3yii2\d3printeripp\enums\PrinterState;
use d3yii2\d3printeripp\types\PrinterAttributes;
use yii\base\Component;

/**
 * Class AlertConfig
 * @package d3yii2\d3printer\logic\settings
 *
 * @property-read string $documentSize
 */
class AlertConfig extends Component
{
    public int $cartridgeMinValue = 10;
    public int $drumMinValue = 10;
    public string $paperSize = 'A4';
    public string $paperType = '27';
    public string $printOrientation = 'Portrait';
    public string $sleepAfter = '15';
    public string $emailFrom = 'system@localhost';
    public ?string $emailTo = null;
    public ?string $emailSubject = null;
    public string $emailTemplate = 'This is alert about the Printer %s ';

/**
 * jāskatas ruļļus no vendor/d3yii2/d3printeripp/logic/PrinterSupplies.php
 */
    public function rules(): array
    {
        return [
            [
                'name' => PrinterAttributes::PRINTER_INFO,
                'label' => 'Informācija',
            ],
            [
                'name' => PrinterAttributes::PRINTER_STATE,
                'label' => 'Statuss',
                'valueLabelClass' => PrinterState::class,
            ],
            [
                'name' => PrinterAttributes::PRINTER_STATE_REASONS,
                'label' => 'Iemesls',
            ],
            [
                'name' => PrinterAttributes::PRINTER_INPUT_TRAY,
                'label' => 'Papīra padeve',
                'csvStringParam' => 'status',
                'enums' => [
                    0 => 'Ok',
                    19=> 'Nav Papīra'
                ]

            ],
            [
                'name' => PrinterAttributes::MARKER_LEVELS,
                'label' => 'Krtridžš',
                //'minValue' => 10,
            ],
        ];
    }

    /**
     * @return AlertConfigRule[]
     */
    public function getRules(): array
    {
        return array_map(
            static fn(array $rule): AlertConfigRule => AlertConfigRule::fromArray($rule),
            $this->rules()
        );
    }

    /**
     * @return string
     */
    public function getDocumentSize(): string
    {
        return $this->paperSize;
    }

    /**
     * @return string
     */
    public function getPaperType(): string
    {
        return $this->paperType;
    }

    /**
     * @return string
     */
    public function getSleepAfter(): string
    {
        return $this->sleepAfter;
    }

    /**
     * @return string
     */
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
    public function getEmailSubject(string $printerName): string
    {
        return $this->emailSubject ?? 'Printer ' . $printerName . ' Alert';
    }
}
