<?php

namespace d3yii2\d3printeripp;

use d3system\yii2\base\D3Module;
use Yii;
use yii\log\FileTarget;

/**
 * Class Module
 * @package d3yii2\d3printeripp
 */
class Module extends D3Module
{
    public $controllerNamespace = 'd3yii2\d3printeripp\controllers';
    
    /**
     * @return string
     */
    public function getLabel(): string
    {
        return Yii::t('d3printeripp', 'd3yii2/d3printeripp');
    }
}
