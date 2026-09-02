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

namespace SolidInvoice\ClientBundle\Exception;

use RuntimeException;
use SolidInvoice\ClientBundle\Entity\Client;

final class InsufficientCreditException extends RuntimeException
{
    public function __construct(Client $client)
    {
        parent::__construct(
            sprintf('Client "%s" does not have sufficient credit to complete this operation.', (string) $client->getName())
        );
    }
}
