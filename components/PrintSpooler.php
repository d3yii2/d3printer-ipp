<?php

namespace d3yii2\d3printeripp\components;

use Exception;
use Yii;
use yii\base\Component;

class PrintSpooler extends Component
{
    /**
     * @var string[]
     */
    public array $printersComponentNames = [];
    /**
     * @var mixed
     */
    public $outCallback;

    public function run(): void
    {
        /** @var BasePrinter|string[] $printers */
        $printers = [];
        $printerProblems = [];
        $printerFilesProblems = [];
        $canNotUnlinkFiles = [];
        foreach ($this->printersComponentNames as $printerComponentName) {
            /**
             * load printer, if not loaded yet
             */
            if (!isset($printers[$printerComponentName])) {
                $printer = false;
                try {
                    if (!Yii::$app->has($printerComponentName)) {
                        $msg = 'Not found printer component with name: "' . $printerComponentName . '"';
                        $this->out($msg);
                        Yii::error($msg);
                    } elseif (!$printer = Yii::$app->get($printerComponentName, false)) {
                        $msg = 'Can not initiate printer component with name: "' . $printerComponentName . '"';
                        $this->out($msg);
                        Yii::error($msg);
                    }
                } catch (Exception $e) {
                    $msg = 'Can not initiate printer component with name: "' . $printerComponentName . '"';
                    $this->out($msg);
                    Yii::error([
                        'exception' => $e,
                        'msg' => $msg
                    ]);
                }
                $printers[$printerComponentName] = $printer;
            }

            /**
             * if printer no loaded, ignore it
            * @var BasePrinter|null $printer
            */
            if (!$printer = $printers[$printerComponentName] ?? null) {
                continue;
            }

            /**
             * try to get spool directory files
             */
            try {
                $spoolDirectoryFiles = $printer->getSpoolDirectoryFiles();
            } catch (Exception $e) {
                $msg = 'Try to get spool directory files.'
                    . ' Printer component: ' . $printerComponentName;
                $fullMsg = $msg . PHP_EOL . $e->getMessage();
                if (($printerProblems[$printerComponentName] ?? false) !== $fullMsg) {
                    Yii::error([
                        'exception' => $e,
                        'msg' => $msg
                    ]);
                    $printerProblems[$printerComponentName] = $fullMsg;
                }
                continue;
            }
            foreach ($spoolDirectoryFiles as $file) {
                if (in_array($file, $canNotUnlinkFiles, true)) {
                    continue;
                }
                try {
                    $this->out($printerComponentName . ': Print file: ' . $file);
                    $printer->printFile($file);
                } catch (Exception $e) {
                    $msg = 'Try print file.'
                        . ' Printer component: ' . $printerComponentName
                        . ' File: ' . $file;
                    if (!($printerFilesProblems[$printerComponentName][$file] ?? false)) {
                        $this->out($msg);
                        Yii::error([
                            'exception' => $e,
                            'msg' => $msg
                        ]);
                        $printerFilesProblems[$printerComponentName][$file] = $e->getMessage();
                    } else {
                        $this->out('E');
                    }
                    continue;
                }
                try {
                    if (!$printer->deleteSpoolDirectoryFile($file)
                        && !in_array($file, $canNotUnlinkFiles, true)
                    ) {
                        $canNotUnlinkFiles[] = $file;
                        $msg = 'Can not delete file: '
                            . ' Printer component: ' . $printerComponentName
                            . ' File: ' . $file;
                        Yii::error($msg);
                    }
                } catch (Exception $e) {
                    if (!in_array($file, $canNotUnlinkFiles, true)) {
                        $canNotUnlinkFiles[] = $file;
                        $msg = ' Can not delete file.'
                            . ' Printer component: ' . $printerComponentName
                            . ' File: ' . $file;
                        $this->out($msg);
                        Yii::error([
                            'exception' => $e,
                            'msg' => $msg
                        ]);
                    }
                }
                $this->out('Ok');
            }
        }
    }

    public function out(string $msg): void
    {
        if (!$this->outCallback || !is_callable($this->outCallback)) {
            return;
        }
        call_user_func($this->outCallback, $msg);
    }
}
