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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;

#[CoversClass(TaxIdentifier::class)]
final class TaxIdentifierTest extends TestCase
{
    public function testSetClientInheritsCompanyFromClient(): void
    {
        $company = new Company();

        $client = $this->createMock(Client::class);
        $client->method('getCompany')->willReturn($company);

        $identifier = new TaxIdentifier();
        $identifier->setClient($client);

        self::assertSame($company, $identifier->getCompany());
    }

    public function testSetClientToNullDoesNotOverwriteExistingCompany(): void
    {
        $company = new Company();

        $identifier = new TaxIdentifier();
        $identifier->setCompany($company);
        $identifier->setClient(null);

        self::assertSame($company, $identifier->getCompany());
    }

    public function testSetClientReturnsFluentInterface(): void
    {
        $identifier = new TaxIdentifier();

        self::assertSame($identifier, $identifier->setClient(null));
    }
}
