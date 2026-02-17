<?php

namespace d3yii2\d3printeripp\components\rules;

class Updated implements RulesInterface
{

    public ?string $dataUpdatedTime = null;

    public static function getAttributeName(): string
    {
        return '';
    }

    public function getLabel(): string
    {
        return 'Informācija atjaunota';
    }

    public function getValueLabel()
    {
        return $this->dataUpdatedTime;
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
        return self::TYPE_OTHER;
    }
}
