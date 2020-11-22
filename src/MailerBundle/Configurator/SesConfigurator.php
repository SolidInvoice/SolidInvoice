<?php

declare(strict_types=1);

namespace SolidInvoice\MailerBundle\Configurator;

use SolidInvoice\MailerBundle\Form\Type\TransportConfig\SesTransportConfigType;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SesConfigurator implements ConfiguratorInterface
{
    public function getForm(): string
    {
        return SesTransportConfigType::class;
    }

    public function getName(): string
    {
        return 'Amazon SES';
    }

    public function configure(array $config): Dsn
    {
        $dsn = \sprintf('ses+api://%s:%s@default', $config['accessKey'], $config['accessSecret']);
        if (\array_key_exists('region', $config) && null !== $config['region']) {
            $dsn .= "?region={$config['region']}";
        }

        return Dsn::fromString($dsn);
    }
}