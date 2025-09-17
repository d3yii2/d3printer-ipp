<?php

namespace d3yii2\d3printeripp\controllers;

use d3yii2\d3printeripp\components\PrinterIPPComponent;
use yii\helpers\Html;

class StatusController extends \yii\web\Controller
{
    private PrinterIPPComponent $printerManager;

    public function init()
    {
        $this->printerManager = Yii::$app->printerManager;
    }

    public function actionIndex(string $printerSlug = null)
    {
        $this->response->format = \yii\web\Response::FORMAT_JSON;

        $response = [];

        if (!$printerSlug) {
            $response['status'] = 'error';
            $response['message'] = 'Printer slug is required';
            return $response;
        }

        $printer = $this->printerManager->getPrinter($printerSlug);

        if (!$printer) {
            $response['status'] = 'error';
            $response['message'] = 'Printer not found';
            return $response;
        }

        $status = $printer->getFullStatus();

        $response = [
            'printerName' => $status['printerName'],
            'printerAccessUrl' => $status['printerAccessUrl'],
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
                        'label' => Yii::t('d3printeripp','Cartridge'),
                        'value' => $displayData['cartridge'],
                    ],
                    [
                        'label' => Yii::t('d3printeripp','Drum'),
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
        ];

        /*foreach ($displayData['lastLoggedErrors'] as $error) {
            $data['lastLoggedErrors'][] = str_replace(PHP_EOL, '<br>', $error);
        }*/

        return $response;
    }
}