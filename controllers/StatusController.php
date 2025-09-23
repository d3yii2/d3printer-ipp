<?php
declare(strict_types=1);

namespace d3yii2\d3printeripp\controllers;

use d3yii2\d3printeripp\components\PrinterIPPComponent;
use Exception;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 *
 */
class StatusController extends Controller
{
    private ?PrinterIPPComponent $printerManager = null;

    public $enableCsrfValidation = false;

    /**
     * {@inheritdoc}
     * @since 2.0.36
     */
    public function init()
    {
        try {
            parent::init();

            $this->printerManager = Yii::$app->printerManager;
            Yii::$app->response->formatters[Response::FORMAT_JSON] = [
                'class' => 'yii\web\JsonResponseFormatter',
            ];
        } catch (Exception $e) {
            Yii::error($e->getMessage());
        }
    }

    /**
     * @param string|null $printerSlug
     * @return array|array[]
     */
    public function actionIndex(string $printerSlug = null): array
    {
        $response = [];

        if (!$printerSlug) {
            $response['status'] = 'error';
            $response['message'] = 'Printer slug is required';
            return $response;
        }

        if (!$this->printerManager) {
            $response['status'] = 'error';
            $response['message'] = 'Failed to initialize Printer';
            return $response;
        }

        try {
            $printer = $this->printerManager->getPrinter($printerSlug);

            if (!$printer) {
                $response['status'] = 'error';
                $response['message'] = 'Printer not found';
                return $response;
            }

            $status = $printer->getFullStatus();

            $response = [
                'printerName' => $status['system']['model'],
                'printerAccessUrl' => $status['system']['deviceUri'],
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
                            'value' => $status['system']['state'],
                        ],
                        [
                            'label' => Yii::t('d3printeripp', 'Cartridge'),
                            'value' => $status['supplies']['level'],
                        ],
                        [
                            'label' => Yii::t('d3printeripp', 'Drum'),
                            'value' => '?'
                        ],
                        [
                            'label' => Yii::t('d3printeripp', 'FTP status'),
                            'value' => $status['ftp'],
                        ],
                        [
                            'label' => Yii::t('d3printeripp', 'Spooler'),
                            'value' => $status['spooler']['deadFileExists'] ? 'Dead' : Yii::t('d3printeripp', 'OK'),
                        ],
                        [
                            'label' => Yii::t('d3printeripp', 'IP'),
                            'value' => '?',
                        ],
                        [
                            'label' => Yii::t('d3printeripp', 'Daemon Status'),
                            'value' => $status['daemon']['status'],
                        ],
                    ],
                ],
                //'deviceErrors' => $displayData['deviceErrors'],
                //'lastLoggedErrors' => []
            ];

            /*foreach ($displayData['lastLoggedErrors'] as $error) {
                $data['lastLoggedErrors'][] = str_replace(PHP_EOL, '<br>', $error);
            }*/

            return ['data' => $response];
        } catch (Exception $e) {
            $response['status'] = 'error';
            $response['message'] = $e->getMessage();
            return $response;
        }
    }
}
