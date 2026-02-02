<?php

declare(strict_types=1);

namespace d3yii2\d3printeripp\components;


class PrinterAttributes extends \obray\ipp\PrinterAttributes
{
    public function getAllAttributes(): array
    {
        return $this->attributes;
    }
}