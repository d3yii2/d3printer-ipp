<?php

namespace d3yii2\d3printeripp\controllers;

use d3yii2\d3printeripp\Module;
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
    public function actionDashboard(string $printerComponentName)
    {
        if (!Yii::$app->has($printerComponentName)) {
            return 'Not found printer component with name: "' . $printerComponentName . '"';
        }
        if (!$printer = Yii::$app->get($printerComponentName)) {
            return 'Not found printer component with name: "' . $printerComponentName . '"';
        }
        return $this->render(
            'ipp-panel',
            [
                'printer' => $printer,
                'alert' => $printer->getStatusFromCache()
            ]
        );
    }

}