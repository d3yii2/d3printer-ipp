<?php

namespace doc\examples;

use d3yii2\d3printeripp\components\AlertConfig;
use d3yii2\d3printeripp\components\rules\FilesCountInSpooler;
use d3yii2\d3printeripp\components\rules\MarkerLevels;
use d3yii2\d3printeripp\components\rules\PrinterInfo;
use d3yii2\d3printeripp\components\rules\PrinterInputTray;
use d3yii2\d3printeripp\components\rules\PrinterState;
use d3yii2\d3printeripp\components\rules\PrinterStateReasons;
use d3yii2\d3printeripp\components\rules\PrintTestPage;
use d3yii2\d3printeripp\components\rules\ReloadStatus;
use d3yii2\d3printeripp\components\rules\Updated;


class IppPrinter3002dnAlertConfig extends AlertConfig
{
    public function rules(): array
    {
        return [
            [
                'className' => PrinterInfo::class,
            ],
            [
                'className' => FilesCountInSpooler::class,
            ],
            [
                'className' => PrinterState::class,
            ],
            [
                'className' => PrinterStateReasons::class,
            ],
            [
                'className' => PrinterInputTray::class,
            ],
            [
                'className' => MarkerLevels::class,
                'minValue' => 10,
            ],
            [
                'className' => Updated::class,
            ],
            [
                'className' => PrintTestPage::class,
            ],
            [
                'className' => ReloadStatus::class,
            ]
            //+queued-job-count
        ];
    }
}