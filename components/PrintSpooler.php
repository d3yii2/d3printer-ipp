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

    /** @var BasePrinter|string[] $printers */
    private $printers = [];

    private array $printerProblems = [];
    private array $printerFilesProblems = [];
    private array $canNotUnlinkFiles = [];
    
    public function run(): void
    {
        $this->printers = [];
        $this->printerProblems = [];
        $this->printerFilesProblems = [];
        $this->canNotUnlinkFiles = [];
        foreach ($this->printersComponentNames as $printerComponentName) {
            /**
             * load printer, if not loaded yet
             */
            if (!isset($this->printers[$printerComponentName])) {
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
                $this->printers[$printerComponentName] = $printer;
            }

            /**
             * if printer no loaded, ignore it
            * @var BasePrinter|null $printer
            */
            if (!$printer = $this->printers[$printerComponentName] ?? null) {
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
                if (($this->printerProblems[$printerComponentName] ?? false) !== $fullMsg) {
                    $this->out($fullMsg);
                    Yii::error([
                        'exception' => $e,
                        'msg' => $msg
                    ]);
                    $this->printerProblems[$printerComponentName] = $fullMsg;
                }
                continue;
            }
            foreach ($spoolDirectoryFiles as $file) {
                if (in_array($file, $this->canNotUnlinkFiles, true)) {
                    continue;
                }
                try {
                    $this->out($printerComponentName . ': Print file: ' . $file);
                    $printer->printFile($file);
                } catch (Exception $e) {
                    $exceptionMessage = $e->getMessage();
                    if (strpos($exceptionMessage, 'Error: server-error-busy') !== false) {
                        $this->out('printer busy');
                        break;
                    }
                    $msg = 'Try print file.'
                        . ' Printer component: ' . $printerComponentName
                        . ' File: ' . $file;
                    if (!($this->printerFilesProblems[$printerComponentName][$file] ?? false)) {
                        $this->out($msg . ' Error: ' .  $exceptionMessage);
                        Yii::error([
                            'exception' => $e,
                            'msg' => $msg
                        ]);
                        $this->printerFilesProblems[$printerComponentName][$file] = $exceptionMessage;
                    } else {
                        $this->out('E');
                    }
                    continue;
                }
                try {
                    if (!$printer->deleteSpoolDirectoryFile($file)
                        && !in_array($file, $this->canNotUnlinkFiles, true)
                    ) {
                        $this->canNotUnlinkFiles[] = $file;
                        $msg = 'Can not delete file: '
                            . ' Printer component: ' . $printerComponentName
                            . ' File: ' . $file;
                        Yii::error($msg);
                    }
                } catch (Exception $e) {
                    if (!in_array($file, $this->canNotUnlinkFiles, true)) {
                        $this->canNotUnlinkFiles[] = $file;
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
