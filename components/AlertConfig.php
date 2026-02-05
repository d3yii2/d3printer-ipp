<?php

declare(strict_types=1);

namespace d3yii2\d3printeripp\components;

use d3yii2\d3printeripp\components\rules\RulesInterface;
use d3yii2\d3printeripp\logic\PrinterAttributes;
use yii\base\Component;

/**
 * Class AlertConfig
 * @package d3yii2\d3printer\logic\settings
 *
 * @todo jāpieliek lastUpdate rule, kurai jauzdod expire intervals
 */
abstract class AlertConfig extends Component
{
    /** @var RulesInterface[]  */
    private array $loadedRule = [];
    public string $loadedTime = '';

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

    public function loadAttributes(PrinterAttributes $attributes): void
    {
        $this->loadedTime = date('Y-m-d H:i:s');
        foreach ($this->getRules() as $rule) {
            $ruleClassName = $rule['className'];
            $value = $attributes
                ->getAttribute($ruleClassName::getAttributeName())
                ->getAttributeValue();
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
}
