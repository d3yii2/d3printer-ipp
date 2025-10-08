<?php
declare(strict_types=1);

namespace d3yii2\d3printeripp\controllers;

use d3yii2\d3printeripp\components\PrinterIPP;
use Exception;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 *
 */
class StatusController extends Controller
{
    private ?PrinterIPP $printerIPP = null;

    public $enableCsrfValidation = false;

    /**
     * {@inheritdoc}
     * @since 2.0.36
     */
    public function init()
    {
        try {
            parent::init();

            $this->printerIPP = Yii::$app->printerIPP;
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

        if (!$this->printerIPP) {
            $response['status'] = 'error';
            $response['message'] = 'Failed to initialize Printer';
            return $response;
        }

        try {
            $printer = $this->printerIPP->getPrinter($printerSlug);

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
                            'label' => Yii::t('d3printeripp', 'IP'),
                            'value' => '?',
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
