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

use SolidInvoice\MailerBundle\Form\Type\TransportConfig\UsernamePasswordTransportConfigType;
use Symfony\Component\Mailer\Transport\Dsn;

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
