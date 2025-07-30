<?php

namespace d3yii2\d3printeripp\logic\panel;

use d3yii2\d3printeripp\interfaces\PrinterInterface;
use d3yii2\d3printeripp\logic\BasePrinter;
use Yii;
use yii\base\Exception;
use yii\helpers\Html;
use d3yii2\d3printer\logic\health\DaemonHealth;

class DisplayDataLogic
{
    /**
     * @var PrinterInterface $printer
     */
    public $emptyDefaultValue = '-';

    protected string $printerSlug;

    protected BasePrinter $printer;

    protected $displayData = [];

    public const DISPLAY_VERTICAL = 'vertical';
    public const DISPLAY_INLINE = 'inline';

    /**
     * @param string $printerSlug
     */
    public function __construct(string $printerSlug)
    {
        $this->printerSlug = $printerSlug;
        $printerManager = \Yii::$app->printerManager;
        $this->printer = $printerManager->getPrinter($printerSlug);
    }


    /**
     * @param string $value
     */
    protected function getDisplayValue($value): string
    {
        return empty($value) && is_string($value) ? $this->emptyDefaultValue : trim($value);
    }


    /**
     * @return string
     * @throws \yii\base\Exception
     */
    protected function getFTPStatusDisplayValue(): string
    {
        $isOk = !$this->printer->existDeadFile();

        return $isOk
            ? Html::tag('span', 'OK', ['style' => 'color:darkgreen'])
            : Html::tag('span', Yii::t('d3printer', 'Down'), ['style' => 'color:red']);
    }

    /**
     * @throws \yii\base\Exception
     */
    public function getSpoolerFilesCount(): int
    {
        return count($this->printer->getSpoolDirectoryFiles());
    }

    public function getDaemonStatus(): string
    {
        if ($this->daemonHealth->statusOk()) {
            return Html::tag('span', $this->daemonHealth->getStatus(), ['style' => 'color:darkgreen']);
        }

        $status = $this->daemonHealth->getStatus();
        $statusOutput = $status !== DaemonHealth::STATUS_UNKNOW ? $status : sprintf('%s (%s)', $status, $this->daemonHealth->getRawStatus());

        return Html::tag('span', $statusOutput, ['style' => 'color:red']);
    }

    /**
     * Return data for ThTableSimple widget
     * @param string $direction
     * @return array
     */
    public function getTableDisplayData(string $direction = self::DISPLAY_VERTICAL): array
    {
        $displayData['printerName'] = $this->printer->getName();
        $displayData['ip'] = $this->printer->getConfig()->getHost();
        $displayData['printerAccessUrl'] = $this->printer->getConfig()->getUri();
        $displayData['lastLoggedErrors'] = 'TODO';//$this->deviceHealth->logger->getLastLoggedErrors();
        $displayData['printerCode'] = $this->printerSlug;

        $suppliesStatus = $this->printer->getSuppliesStatus();
        $displayData['status'] = $suppliesStatus['status'];
        $displayData['cartridge'] = $suppliesStatus['level'];
        $displayData['drum'] = ''; //$suppliesStatus[''];
        $displayData['deviceErrors'] = ''; //$this->deviceHealth->logger->getErrors());
        $displayData['ftpState'] = ''; //$this->getFTPStatusDisplayValue());
        $displayData['spool'] = ''; //$this->getSpoolerFilesCount());
        $displayData['daemonStatus'] = ''; //$this->getDaemonStatus());


        $data = self::DISPLAY_VERTICAL === $direction
            ? [
                'printerName' => $displayData['printerName'],
                'printerAccessUrl' => $displayData['printerAccessUrl'],
                'info' => [
                    'columns' => [
                        [
                            'header' => '',
                            'attribute' => 'label',
                        ],
                        [
                            'header' => '',
                            'attribute' => 'value',
                        ],
                    ],
                    'data' => [
                        [
                            'label' => Yii::t('d3printer', 'Status'),
                            'value' => $displayData['status'],
                        ],
                        [
                            'label' => Yii::t('d3printer', 'Cartridge'),
                            'value' => $displayData['cartridge'],
                        ],
                        [
                            'label' => Yii::t('d3printer', 'Drum'),
                            'value' => $displayData['drum']
                        ],
                        [
                            'label' => Yii::t('d3printer', 'FTP status'),
                            'value' => $displayData['ftpState'],
                        ],
                        [
                            'label' => Yii::t('d3printer', 'Spooler'),
                            'value' => $displayData['spool'],
                        ],
                        [
                            'label' => Yii::t('d3printer', 'IP'),
                            'value' => $displayData['ip'],
                        ],
                        [
                            'label' => Yii::t('d3printer', 'Daemon Status'),
                            'value' => $displayData['daemonStatus'],
                        ],
                    ],
                ],
                //'deviceErrors' => $displayData['deviceErrors'],
                //'lastLoggedErrors' => []
            ]
            : [
                'info' => [
                    'columns' => [
                        [
                            'header' => 'Name',
                            'attribute' => 'name',
                        ],
                        [
                            'header' => 'Status',
                            'attribute' => 'status',
                        ],
                        [
                            'header' => 'Cartridge',
                            'attribute' => 'cartridge',
                        ],
                        [
                            'header' => 'Drum',
                            'attribute' => 'drum',
                        ]
                    ],
                    'data' => [
                        [
                            'name' => Html::a($displayData['printerName'], $displayData['printerAccessUrl']),
                            'status' => $displayData['status'],
                            'cartridge' => $displayData['cartridge'],
                            'drum' => $displayData['drum']
                        ],
                    ],
                ],
                //'deviceErrors' => $displayData['deviceErrors'],
                //'lastLoggedErrors' => []
            ];

        /*foreach ($displayData['lastLoggedErrors'] as $error) {
            $data['lastLoggedErrors'][] = str_replace(PHP_EOL, '<br>', $error);
        }*/

        return $data;
    }
}
