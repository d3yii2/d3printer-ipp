<?php

namespace d3yii2\d3printeripp\obray;

class JobAttributes extends \obray\ipp\JobAttributes
{
    public function getAllAttributes(): array
    {
        return $this->attributes;
    }
}