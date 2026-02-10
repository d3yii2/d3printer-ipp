<?php

declare(strict_types=1);

namespace d3yii2\d3printeripp\components;

use Yii;
use yii\base\Component;

/**
 * use for defining printer alert email sending receivers
 * define one component and set in all ipp printer components config
 */
final class Mailer extends Component
{
    public ?string $from = null;
    public ?array $to = null;
    public ?string $subject = 'System "{systemName}",Problems with the "{name}" {deviceName}';

    public function send(
        string $printerComponentName,
        string $body,
        string $deviceName = 'printer'
    ): void {
        $subject = Yii::t(
            'd3printeripp',
            $this->subject,
            [
                'systemName' => Yii::$app->name,
                'name' => $printerComponentName,
                'deviceName' => $deviceName
            ]
        );
        Yii::$app->mailer
            ->compose()
            ->setFrom($this->from)
            ->setTo($this->to)
            ->setSubject($subject)
            ->setTextBody($body)
            ->send();
    }
}
