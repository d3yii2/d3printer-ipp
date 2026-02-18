<?php

namespace d3yii2\d3printeripp\controllers;

use d3yii2\d3printeripp\components\BasePrinter;
use d3yii2\d3printeripp\Module;
use Exception;
use unyii2\yii2panel\Controller;
use Yii;
use yii\base\InvalidConfigException;
use yii\filters\AccessControl;

/**
 * @property Module $module
 */
class PrinterPanelController extends Controller
{

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'dashboard',
                        ],
                        'roles' => $this->module->panelViewRoleNames??['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @throws InvalidConfigException
     */
    public function actionDashboard(string $printerComponentName): string
    {
        $errorMessage = null;
        $printer = null;
        $alert = null;
        try {
            if (!Yii::$app->has($printerComponentName)) {
                $errorMessage = 'Not found printer component with name: "' . $printerComponentName . '"';
                Yii::error($errorMessage);
                /** @var BasePrinter $printer */
            } elseif (!$printer = Yii::$app->get($printerComponentName)) {
                $errorMessage = 'Not found printer component with name: "' . $printerComponentName . '"';
                Yii::error($errorMessage);
            } else {
                $printer->printerComponentName = $printerComponentName;
                $alert = $printer->getStatusFromCache();
            }
        } catch (Exception $e) {
            Yii::error($e);
            $errorMessage = $e->getMessage();
        }
        return $this->render(
            'ipp-panel',
            [
                'printer' => $printer,
                'alert' => $alert,
                'errorMessage' => $errorMessage
            ]
        );
    }

}