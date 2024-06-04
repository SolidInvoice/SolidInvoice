<?php

declare(strict_types=1);

use SolidInvoice\NotificationBundle\Notification\NotificationMessage;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Config\FrameworkConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (FrameworkConfig $config): void {
    $messengerConfig = $config->messenger();

    $messengerConfig->resetOnMessage(true);
};
