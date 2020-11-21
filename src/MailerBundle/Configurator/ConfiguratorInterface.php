<?php

declare(strict_types=1);

namespace SolidInvoice\MailerBundle\Configurator;

use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Contracts\Translation\TranslatorInterface;

interface ConfiguratorInterface
{
    public function getName(TranslatorInterface $translator): string;

    public function getForm(): string;

    public function configure(array $config): Dsn;
}