<?php

declare(strict_types=1);

use Symfony\Config\SwiftmailerConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

/*return static function (SwiftmailerConfig $config): void {
    $config
        ->defaultMailer('default')
        ->mailer('default')
            ->transport(env('mailer_transport'))
            ->host(env('mailer_host'))
            ->username(env('mailer_user'))
            ->password(env('mailer_password'))
            ->encryption(env('mailer_encryption'))
            ->port(env('mailer_port'))
            ->spool()
            ->type('memory');
};*/

return static fn () => null;
