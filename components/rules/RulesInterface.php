<?php

namespace d3yii2\d3printeripp\components\rules;
interface RulesInterface
{
    public const TYPE_RULE = 'rule';
    public const TYPE_PRINT = 'print';
    public const TYPE_RELOAD = 'reload';
    public const TYPE_OTHER = 'other';
    public static function getAttributeName(): string;
    public function getLabel(): string;
    public function getValueLabel();
    public function isWarning(): bool;
    public function isError(): bool;
    public function getWarningMessage(): string;
    public function getErrorMessage(): string;

    public static function getType(): string;
}