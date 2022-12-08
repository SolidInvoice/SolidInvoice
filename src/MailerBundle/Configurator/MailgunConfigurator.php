<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MailerBundle\Configurator;

use SolidInvoice\MailerBundle\Form\Type\TransportConfig\MailgunApiTransportConfigType;
use Symfony\Component\Mailer\Transport\Dsn;

/**
 * @see \SolidInvoice\MailerBundle\Tests\Configurator\MailgunConfiguratorTest
 */
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
