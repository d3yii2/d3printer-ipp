<?php

declare(strict_types=1);

namespace d3yii2\d3printeripp\components;

use d3yii2\d3printeripp\components\rules\RulesInterface;
use obray\ipp\PrinterAttributes as IPPPrinterAttributes;
use yii\base\Component;


/**
 * Class AlertConfig
 * @package d3yii2\d3printer\logic\settings
 *
 *
 * @property-read string[] $warningMessages list of warning messages
 * @property-read string[] $errorMessages list of error messages
 * @property-read array[]|RulesInterface[] $rules list of rules
 * @property-read array[] $displayList list of display items
 */
abstract class AlertConfig extends Component
{


    /** @var RulesInterface[]  */
    private array $loadedRule = [];


    public string $loadedTime = '';

    public bool $isWarningChanged = false;

    /**
     * jāskatas ruļļus no vendor/d3yii2/d3printeripp/logic/PrinterSupplies.php
     * @return array[]
     */
    abstract public function rules(): array;

    /**
     * @return RulesInterface[]
     */
    public function getRules(): array
    {
        return $this->rules();
    }


    public function loadAttributes(IPPPrinterAttributes $attributes): void
    {
        $this->loadedTime = date('Y-m-d H:i:s');
        foreach ($this->getRules() as $rule) {
            $ruleClassName = $rule['className'];
            $ruleAttributeName = $ruleClassName::getAttributeName();
            $ruleAttributes = $attributes->$ruleAttributeName;

            if (!is_array($ruleAttributes)) {
                $ruleAttributes = [$ruleAttributes];
            }
            foreach ($ruleAttributes as $ruleEttribute) {
                $value = $ruleEttribute->getAttributeValue();
                /** @var RulesInterface $ruleObject */
                $ruleObject = new $ruleClassName($value);
                foreach ($rule as $propertyName => $propertyValue) {
                    if ($propertyName === 'className') {
                        continue;
                    }
                    $ruleObject->$propertyName = $propertyValue;
                }
                $this->loadedRule[] = $ruleObject;
            }
        }
    }

    public function hasWarning(): bool
    {
        foreach ($this->loadedRule as $ruleObject) {
            if ($ruleObject->isWarning()) {
                return true;
            }
        }
        return false;
    }

    public function hasError(): bool
    {
        foreach ($this->loadedRule as $ruleObject) {
            if ($ruleObject->isError()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string[]
     */
    public function getWarningMessages(): array
    {
        $list = [];
        foreach ($this->loadedRule as $ruleObject) {
            if ($ruleObject->isWarning()) {
                $list[] = $ruleObject->getWarningMessage();
            }
        }
        return $list;
    }

    /**
     * @return string[]
     */
    public function getErrorMessages(): array
    {
        $list = [];
        foreach ($this->loadedRule as $ruleObject) {
            if ($ruleObject->isWarning()) {
                $list[] = $ruleObject->getWarningMessage();
            }
        }
        return $list;
    }

    /**
     * @return array<int, array{
     *      label: string,
     *      value: string,
     *      isWarning: bool,
     *      isError: bool
     *  }>
     */
    public function getDisplayList(): array
    {
        $list = [];
        foreach ($this->loadedRule as $ruleObject) {
            $list[] = [
                'label' => $ruleObject->getLabel(),
                'value' => $ruleObject->getValueLabel(),
                'isWarning' => $ruleObject->isWarning(),
                'isError' => $ruleObject->isError(),
            ];
        }
        return $list;
    }

    public function warningMustBeSent(self $prev): bool
    {
        return  ( $this->getWarningMessages() !== $prev->getWarningMessages())
            && ($this->hasWarning() || $this->hasError());
    }

    /**
     */
    public function createEmailBody(): string
    {
        $html = '';
        if ($this->hasError()) {
            $html .= 'Errors: <br> - ' . implode('<br> - ', $this->getErrorMessages());
        }
        if ($this->hasError()) {
            $html .= 'Warnings: <br>- ' . implode('<br> - ', $this->getErrorMessages());
        }
        $list = [];
        foreach ($this->getDisplayList() as $item) {
            $list[] = $item['label'] . ': ' . $item['value'];
        }
        $html .= 'Status: <br>- ' . implode('<br>- ', $list);
        return $html;
    }
}
