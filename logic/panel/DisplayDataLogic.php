<?php

namespace d3yii2\d3printeripp\logic\panel;

use d3yii2\d3printeripp\interfaces\PrinterInterface;
use Yii;
use yii\base\Exception;
use yii\helpers\Html;

class DisplayDataLogic
{
    /**
     * @var PrinterInterface $printer
     */
    public $emptyDefaultValue = '-';

    protected PrinterInterface $printer;

    protected $displayData = [];

    public const DISPLAY_VERTICAL = 'vertical';
    public const DISPLAY_INLINE = 'inline';

    /**
     * @param string $printerSlug
     */
    public function __construct(string $printerSlug)
    {
        $printerManager = \Yii::$app->printerManager;
        $printer = $printerManager->getPrinter($printerSlug);

        if (!$printer) {
            throw new \yii\web\NotFoundHttpException("Printer not found");
        }

        $this->printer = $printer;
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
            : Html::tag('span', Yii::t('d3printeripp', 'Down'), ['style' => 'color:red']);
    }


    public function getDaemonStatus(): string
    {
        if ($this->daemon->statusOk()) {
            return Html::tag('span', $this->daemon->getStatus(), ['style' => 'color:darkgreen']);
        }

        $status = $this->daemon->getStatus();
        $statusOutput = $status !== 'idle' ? $status : sprintf('%s (%s)', $status, $this->daemon->getRawStatus());

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
        $displayData['printerCode'] = $this->printer->getConfig()->getSlug();

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
                            'label' => Yii::t('d3printeripp', 'Status'),
                            'value' => $displayData['status'],
                        ],
                        [
                            'label' => Yii::t('d3printeripp', 'Cartridge'),
                            'value' => $displayData['cartridge'],
                        ],
                        [
                            'label' => Yii::t('d3printeripp', 'Drum'),
                            'value' => $displayData['drum']
                        ],
                        [
                            'label' => Yii::t('d3printeripp', 'FTP status'),
                            'value' => $displayData['ftpState'],
                        ],
                        [
                            'label' => Yii::t('d3printeripp', 'Spooler'),
                            'value' => $displayData['spool'],
                        ],
                        [
                            'label' => Yii::t('d3printeripp', 'IP'),
                            'value' => $displayData['ip'],
                        ],
                        [
                            'label' => Yii::t('d3printeripp', 'Daemon Status'),
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
