<?php

namespace d3yii2\d3printeripp\components\rules;

class PrintTestPage implements RulesInterface
{

    public ?string $printerComponentName = null;

    public static function getAttributeName(): string
    {
        return '';
    }

    public function getLabel(): string
    {
        return 'Izdruka';
    }

    public function getValueLabel()
    {
        return [
            '/d3printeripp/printer/print',
            'printerComponentName' => $this->printerComponentName,
        ];
    }

    public function isWarning(): bool
    {
        return false;
    }

    public function isError(): bool
    {
        return false;
    }

    public function getWarningMessage(): string
    {
        return '';
    }

    public function getErrorMessage(): string
    {
        return '';
    }

    public static function getType(): string
    {
        return self::TYPE_PRINT;
    }
}
