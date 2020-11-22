<?php

declare(strict_types=1);

namespace SolidInvoice\MailerBundle\Configurator;

use SolidInvoice\MailerBundle\Form\Type\TransportConfig\MailgunApiTransportConfigType;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MailgunConfigurator implements ConfiguratorInterface
{
    public function getForm(): string
    {
        return MailgunApiTransportConfigType::class;
    }

    public function getName(): string
    {
        return 'Mailgun';
    }

    public function configure(array $config): Dsn
    {
        return Dsn::fromString(\sprintf('mailgun+api://%s:%s@default', $config['key'], $config['domain']));
    }
}