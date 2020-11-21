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

    public function getName(TranslatorInterface $translator): string
    {
        return $translator->trans('Amazon SES');
    }

    public function configure(array $config): Dsn
    {
        return Dsn::fromString(\sprintf('ses+api://%s:%s@default', $config['accessKey'], $config['accessSecret']));
    }
}