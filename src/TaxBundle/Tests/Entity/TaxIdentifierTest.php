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

namespace SolidInvoice\TaxBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;

final class TaxIdentifierTest extends TestCase
{
    public function testAddingToUnpersistedClientWithoutCompanyDoesNotFail(): void
    {
        $client = new Client();
        $identifier = new TaxIdentifier();

        $client->addTaxIdentifier($identifier);

        self::assertSame($client, $identifier->getClient());
        self::assertNull($identifier->getCompanyId());
    }
}
