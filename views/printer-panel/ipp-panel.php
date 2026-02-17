<?php

use cornernote\returnurl\ReturnUrl;
use d3yii2\d3printeripp\components\AlertConfig;
use d3yii2\d3printeripp\components\BasePrinter;
use eaBlankonThema\widget\ThButton;
use eaBlankonThema\widget\ThExternalLink;
use eaBlankonThema\widget\ThPanel;

/**
 * @var string $errorMessage
 * @var BasePrinter $printer
 * @var AlertConfig $alert
 */


ThPanel::widget();
$body = '';
if ($errorMessage) {
    $body .= '<div class="alert alert-danger">'.$errorMessage.'</div>';
}
if ($printer) {
    $header = ThExternalLink::widget([
        'url' => 'https://' . $printer->host,
        'text' => $printer->name
    ]);
    $body .= '
<div class="table-responsive">
    <table class="table">
        <tbody>
            <tr>
                <td>IP</td>
                <td>'.$printer->host.':'.$printer->port.'</td>
            </tr>';
} else {
    $header = 'Printer Error';
}

if ($alert) {
    foreach ($alert->getDisplayList() as $item) {
        if ($item['ruleObject']::getType() === $item['ruleObject']::TYPE_PRINT) {
            $link = $item['ruleObject']->getValueLabel();
            $link['ru'] = ReturnUrl::getToken();
            $displayValue = ThButton::widget([
                'icon' => ThButton::ICON_PRINT,
                'type' => ThButton::TYPE_SUCCESS,
                'link' => $link
            ]);
        } elseif ($item['ruleObject']::getType() === $item['ruleObject']::TYPE_RELOAD) {
            $link = $item['ruleObject']->getValueLabel();
            $link['ru'] = ReturnUrl::getToken();
            $displayValue = ThButton::widget([
                'icon' => ThButton::ICON_REFRESH,
                'type' => ThButton::TYPE_SUCCESS,
                'link' => $link
            ]);
        } else {
            $displayValue = $item['value'];
        }
        $class = '';
        if ($item['isWarning']) {
            $class = ' class="warning"';
        }
        if ($item['isError']) {
            $class = ' class="danger"';
        }
        $body .= '<tr>
        <td>' . $item['label'] . '</td>
        <td' . $class . '>' . $displayValue . '</td>
        </tr>';
    }
}
$body .= '</tbody></table></div>';
$collapsed = true;
if ($errorMessage
    || !$printer
    || !$alert
    || $alert->hasError()
) {
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