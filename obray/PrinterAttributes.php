<?php

namespace d3yii2\d3printeripp\obray;

class PrinterAttributes extends \obray\ipp\PrinterAttributes
{
    public function getAllAttributes(): array
    {
        return $this->attributes;
    }
}