<?php

declare(strict_types=1);

namespace SolidInvoice\MailerBundle\Configurator;

use SolidInvoice\MailerBundle\Form\Type\TransportConfig\SesTransportConfigType;
use SolidInvoice\MailerBundle\Form\Type\TransportConfig\SmtpTransportConfigType;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SmtpConfigurator implements ConfiguratorInterface
{
    private const DEFAULT_PORT = 25;

    public function getForm(): string
    {
        return SmtpTransportConfigType::class;
    }

    public function getName(TranslatorInterface $translator): string
    {
        return $translator->trans('SMTP');
    }

    public function configure(array $config): Dsn
    {
        return Dsn::fromString(\sprintf('smtp://%s:%s@%s:%d', $config['user'], $config['password'], $config['host'], $config['port'] ?? self::DEFAULT_PORT));
    }
}