<?php

declare(strict_types=1);

namespace SolidInvoice\MailerBundle\Configurator;

use SolidInvoice\MailerBundle\Form\Type\TransportConfig\UsernamePasswordTransportConfigType;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GmailConfigurator implements ConfiguratorInterface
{
    public function getForm(): string
    {
        return UsernamePasswordTransportConfigType::class;
    }

    public function getName(): string
    {
        return 'Gmail';
    }

    public function configure(array $config): Dsn
    {
        return Dsn::fromString(\sprintf('gmail+smtp://%s:%s@default', $config['username'], $config['password']));
    }
}