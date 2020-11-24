<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MailerBundle\Configurator;

use SolidInvoice\MailerBundle\Form\Type\TransportConfig\SmtpTransportConfigType;
use Symfony\Component\Mailer\Transport\Dsn;

final class SmtpConfigurator implements ConfiguratorInterface
{
    private const DEFAULT_PORT = 25;

    public function getForm(): string
    {
        return SmtpTransportConfigType::class;
    }

    public function getName(): string
    {
        return 'SMTP';
    }

    public function configure(array $config): Dsn
    {
        return Dsn::fromString(\sprintf('smtp://%s:%s@%s:%d', $config['user'], $config['password'], $config['host'], $config['port'] ?? self::DEFAULT_PORT));
    }
}