<?php

declare(strict_types=1);

namespace d3yii2\d3printeripp\components;

use d3yii2\d3printeripp\types\PrinterAttributes;
use InvalidArgumentException;

/**
 * Represents one alert-config rule like:
 * [
 *   'type'  => PrinterAttributes::TYPE_PRINTER_ATTRIBUTES,
 *   'name'  => PrinterAttributes::PRINTER_INFO,
 *   'label' => 'Printer Info',
 * ]
 */
final class AlertConfigRule
{
    private string $name;
    private string $label;

    /**
     * Optional class name used to format/map the raw value to a label.
     *
     * @var class-string|null
     */
    private ?string $valueLabelClass;
    private string $displayValue;

    public function __construct(string $name, string $label, ?string $valueLabelClass = null)
    {
        $this->name = $name;
        $this->label = $label;
        $this->valueLabelClass = $valueLabelClass;
    }

    /**
     * @param array{type:mixed,name:mixed,label:mixed} $data
     */
    public static function fromArray(array $data): self
    {
        if (!is_string($data['name']) || $data['name'] === '') {
            throw new InvalidArgumentException('AlertConfigRule.name must be a non-empty string.');
        }

        if (!is_string($data['label']) || $data['label'] === '') {
            throw new InvalidArgumentException('AlertConfigRule.label must be a non-empty string.');
        }

        $valueLabelClass = null;
        if (array_key_exists('valueLabelClass', $data)) {
            if ($data['valueLabelClass'] !== null && (!is_string(
                        $data['valueLabelClass']
                    ) || $data['valueLabelClass'] === '')) {
                throw new InvalidArgumentException(
                    'AlertConfigRule.valueLabelClass must be a non-empty string or null.'
                );
            }
            $valueLabelClass = $data['valueLabelClass'];
        }

        return new self($data['name'], $data['label'], $valueLabelClass);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return class-string|null
     */
    public function getValueLabelClass(): ?string
    {
        return $this->valueLabelClass;
    }

    public function getDisplayValue(): string
    {
        return $this->displayValue;
    }

    /**
     * @param string $displayValue
     */
    public function setDisplayValue(string $displayValue): void
    {
        $this->displayValue = $displayValue;
    }
}