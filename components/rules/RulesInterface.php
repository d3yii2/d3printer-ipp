<?php

namespace d3yii2\d3printeripp\components\rules;
interface RulesInterface
{
    public static function getAttributeName(): string;
    public function getLabel(): string;
    public function getValueLabel(): string;
    public function isWarning(): bool;
    public function isError(): bool;
    public function getWarningMessage(): string;
    public function getErrorMessage(): string;
}