<?php

namespace d3yii2\d3printeripp\obray;

class OperationAttributes extends \obray\ipp\OperationAttributes
{
    public function getAllAttributes(): array
    {
        return $this->attributes;
    }
}