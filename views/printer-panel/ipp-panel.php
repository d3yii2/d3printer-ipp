<?php

use d3yii2\d3printeripp\components\AlertConfig;
use d3yii2\d3printeripp\components\HPPrinter;
use eaBlankonThema\widget\ThExternalLink;
use eaBlankonThema\widget\ThPanel;

/**
 * @var HPPrinter $printer
 * @var AlertConfig $alert
 */

$header = ThExternalLink::widget([
        'url' => 'https://' . $printer->host,
        'text' => $printer->name
    ]);

$body = '
<div class="table-responsive">
    <table class="table">
        <tbody>
';
$body .= '<tr>
        <td>IP</td>
        <td>'.$printer->host.':'.$printer->port.'</td>
        </tr>';
foreach ($alert->getDisplayList() as $item) {
    $class = '';
    if ($item['isWarning']) {
        $class = ' class="warning"';
    }
    if ($item['isError']) {
        $class = ' class="danger"';
    }
    $body .= '<tr>
        <td>'.$item['label'].'</td>
        <td'.$class.'>'.$item['value'].'</td>
        </tr>';
}

$body .= '<tr>
        <td>Informācija atjaunota</td>
        <td>'.$alert->loadedTime.'</td>
        </tr>';

$body .= '</tbody></table></div>';
$collapsed = true;
if ($alert->hasError()) {
    $type = ThPanel::TYPE_DANGER;
    $collapsed = false;
} elseif ($alert->hasWarning()) {
    $type = ThPanel::TYPE_WARNING;
    $collapsed = false;
} else {
    $type = ThPanel::TYPE_SUCCESS;
}
echo ThPanel::widget([
    'type' => $type,
    'leftIcon' => 'fa fa-print',
    'isCollapsed' => $collapsed,
    'showCollapseButton' => true,
    'header' => $header,
    'body' => $body,
]);