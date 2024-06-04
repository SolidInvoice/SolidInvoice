<?php

declare(strict_types=1);

use Symfony\Config\FrameworkConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (FrameworkConfig $frameworkConfig): void {
    $frameworkConfig->mailer()
        ->dsn(env('MAILER_DSN'))
        ->envelope()
            ->sender('SolidInvoice <no-reply@solidinvoice.co>')
    ;
};
