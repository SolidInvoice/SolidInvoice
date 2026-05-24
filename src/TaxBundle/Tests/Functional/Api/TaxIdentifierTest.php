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

namespace SolidInvoice\TaxBundle\Tests\Functional\Api;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use SolidInvoice\TaxBundle\Test\Factory\TaxIdentifierFactory;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

#[Group('functional')]
final class TaxIdentifierTest extends ApiTestCase
{
    use Factories;

    protected function getResourceClass(): string
    {
        return TaxIdentifier::class;
    }

    public function testCreate(): void
    {
        $data = [
            'label' => 'VAT',
            'value' => 'GB123456789',
            'primary' => true,
        ];

        $result = $this->requestPost('/api/tax-identifiers', $data);

        self::assertArrayHasKey('id', $result);
        self::assertTrue(Ulid::isValid($result['id']));
        self::assertSame('VAT', $result['label']);
        self::assertSame('GB123456789', $result['value']);
        self::assertTrue($result['primary']);
    }

    public function testGetCollectionIsScopedByCompany(): void
    {
        TaxIdentifierFactory::createMany(2, ['company' => $this->company]);

        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        TaxIdentifierFactory::createMany(3, ['company' => $otherCompany]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $data = $this->requestGetCollection('/api/tax-identifiers');

        self::assertSame(2, $data['totalItems']);
    }

    public function testCannotAccessTaxIdentifierFromDifferentCompany(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $identifier = TaxIdentifierFactory::createOne(['company' => $otherCompany])->_real();
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request('GET', $this->getIriFromResource($identifier), [
            'headers' => [
                'content-type' => 'application/ld+json',
                'accept' => 'application/ld+json',
            ],
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
