<?php

namespace d3yii2\d3printeripp\components\rules;

class FilesCountInSpooler implements RulesInterface
{

    public ?int $countFilesInSpooler = null;

    public static function getAttributeName(): string
    {
        return '';
    }

    public function getLabel(): string
    {
        return 'Faili spūlerī';
    }

    public function getValueLabel()
    {
        return $this->countFilesInSpooler;
    }

    public function isWarning(): bool
    {
        return false;
    }

    public function isError(): bool
    {
        return $this->countFilesInSpooler > 0;
    }

    public function getWarningMessage(): string
    {
        return '';
    }

    public function getErrorMessage(): string
    {
        if ($this->countFilesInSpooler > 0) {
            return 'Spūlerī ir ' . $this->countFilesInSpooler . ' faili';
        }
        return '';
    }

    public static function getType(): string
    {
        return self::TYPE_OTHER;
    }
}
