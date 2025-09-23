<?php
declare(strict_types=1);

namespace d3yii2\d3printeripp;

use d3system\yii2\base\D3Module;
use Yii;

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
    public static function getLabel(): string
    {
        return Yii::t('d3printeripp', 'd3yii2/d3printeripp');
    }
}
