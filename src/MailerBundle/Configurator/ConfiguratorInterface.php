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

use Symfony\Component\Mailer\Transport\Dsn;

interface ConfiguratorInterface
{
    public function getName(): string;

    public function getForm(): string;

    public function configure(array $config): Dsn;
}
