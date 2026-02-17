<?php

namespace d3yii2\d3printeripp\commands;


use d3system\commands\DaemonController;
use d3yii2\d3printeripp\components\PrintSpooler;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Exception;

class SpoolerCommand extends DaemonController
{

    public function init()
    {
        $this->monoLogFileName = 'printer-spooler';
        $this->monoLogName = 'printer-spooler';
        $this->monoLogRuntimeDirectory = 'logging/printer-spooler';
        parent::init();
    }

    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function actionIndex(
        string $spoolerComponentName,
        ?string $printerComponentNamesCsv = null
    ): int {

        if (!Yii::$app->has($spoolerComponentName)) {
            $errorMessage = 'Not found spooler component with name: "' . $spoolerComponentName . '"';
            Yii::error($errorMessage);
        }
        /** @var PrintSpooler $spooler */
        if (!$spooler = Yii::$app->get($spoolerComponentName)) {
            $errorMessage = 'Not found spooler component with name: "' . $spoolerComponentName . '"';
            Yii::error($errorMessage);
        }
        $spooler->outCallback = function ($msg) {
            $this->out($msg);
        };
        if ($printerComponentNamesCsv) {
            $spooler->printersComponentNames = explode(',', $printerComponentNamesCsv);
        }

        while ($this->loop()) {
            $spooler->run();
        }
    }

}