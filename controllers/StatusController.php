<?php
declare(strict_types=1);

namespace d3yii2\d3printeripp\controllers;


use Exception;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 *
 */
class StatusController extends Controller
{

    public $enableCsrfValidation = false;

    /**
     * {@inheritdoc}
     * @since 2.0.36
     */
    public function init()
    {
        try {
            parent::init();


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


        try {



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
