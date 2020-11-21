<?php

declare(strict_types=1);

namespace SolidInvoice\MailerBundle\Configurator;

use SolidInvoice\MailerBundle\Form\Type\TransportConfig\KeyTransportConfigType;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PostmarkConfigurator implements ConfiguratorInterface
{
    public function getForm(): string
    {
        return KeyTransportConfigType::class;
    }

    public function getName(TranslatorInterface $translator): string
    {
        return $translator->trans('Postmark');
    }

    public function configure(array $config): Dsn
    {
        return Dsn::fromString(\sprintf('postmark+api://%s@default', $config['key']));
    }
}