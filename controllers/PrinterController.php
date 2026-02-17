<?php

namespace d3yii2\d3printeripp\controllers;

use cornernote\returnurl\ReturnUrl;
use d3system\helpers\FlashHelper;
use d3yii2\d3printeripp\components\BasePrinter;
use d3yii2\d3printeripp\Module;
use Exception;
use obray\ipp\exceptions\AuthenticationError;
use obray\ipp\exceptions\HTTPError;
use Yii;
use yii\base\InvalidConfigException;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

/**
 * @property Module $module
 */
class PrinterController extends Controller
{

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'print',
                            'reload-status',
                        ],
                        'roles' => $this->module->panelViewRoleNames ?? ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     *
     * Print a document
     */
    public function actionPrint(string $printerComponentName): Response
    {
        try {
            /** @var BasePrinter $printer */
            $printer = Yii::$app->get($printerComponentName);
            $leftMargin = 72;
            $lineStep = 18;
            // Create a simple test document (PostScript)
            $testDocument = "%!PS-Adobe-3.0\n";
            $testDocument .= "72 720 moveto\n";
            $testDocument .= "/Times-Roman findfont 14 scalefont setfont\n";
            $testDocument .= '(' . date('Y-m-d H:i:s') . ") show\n";
            $testDocument .= $leftMargin . " currentpoint exch pop " . (-$lineStep) . " add moveto\n";
            $testDocument .= '(Printer component: ' . $printerComponentName . "\n) show\n";
            $testDocument .= $leftMargin . " currentpoint exch pop " . (-$lineStep) . " add moveto\n";
            $testDocument .= '(Printer ip: ' . $printer->host . ':' . $printer->port . "\n) show\n";
            $testDocument .= "showpage\n";

            $result = $printer->printContent($testDocument);
            if ($result->statusCode->getClass() === 'successful') {
                FlashHelper::addSuccess('Document printed successfully');
            } else {
                FlashHelper::addError('Error printing document. Error: ' . $result->statusCode->getClass());
            }
        } catch (Exception $e) {
            FlashHelper::addError('Error printing document. Error: ' . $e->getMessage());
            Yii::error($e);
        }
        return $this->redirect(ReturnUrl::getUrl());
    }

    /**
     * @throws InvalidConfigException
     * @throws HTTPError
     * @throws AuthenticationError
     * @throws \yii\base\Exception
     */
    public function actionReloadStatus(string $printerComponentName): Response
    {
        if (!Yii::$app->has($printerComponentName)) {
            $errorMessage = 'Not found printer component with name: "' . $printerComponentName . '"';
            Yii::error($errorMessage);
        }
        /** @var BasePrinter $printer */
        if (!$printer = Yii::$app->get($printerComponentName)) {
            $errorMessage = 'Not found printer component with name: "' . $printerComponentName . '"';
            Yii::error($errorMessage);
        }
        if ($printer) {
            $printer->printerComponentName = $printerComponentName;
            $printer->getStatusFromPrinter();
        }
        return $this->redirect(ReturnUrl::getUrl());
    }
}
