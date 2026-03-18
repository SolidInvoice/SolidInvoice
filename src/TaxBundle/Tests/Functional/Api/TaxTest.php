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

use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Test\Factory\TaxFactory;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class TaxTest extends ApiTestCase
{
    use Factories;

    protected function getResourceClass(): string
    {
        return Tax::class;
    }

    public function testCreate(): void
    {
        $data = [
            'name' => 'VAT',
            'rate' => 15.0,
            'type' => Tax::TYPE_INCLUSIVE,
        ];

        $result = $this->requestPost('/api/taxes', $data);

        self::assertArrayHasKey('id', $result);
        self::assertTrue(Ulid::isValid($result['id']));
        unset($result['id'], $result['@id']);

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource(Tax::class),
            '@type' => 'Tax',
            'name' => 'VAT',
            'rate' => 15.0,
            'type' => Tax::TYPE_INCLUSIVE,
        ], $result);
    }

    public function testGet(): void
    {
        $tax = TaxFactory::createOne([
            'name' => 'GST',
            'rate' => 10.0,
            'type' => Tax::TYPE_EXCLUSIVE,
        ])->_real();

        $data = $this->requestGet($this->getIriFromResource($tax));

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource($tax),
            '@id' => $this->getIriFromResource($tax),
            '@type' => 'Tax',
            'id' => $tax->getId()->toString(),
            'name' => $tax->getName(),
            'rate' => $tax->getRate(),
            'type' => $tax->getType(),
        ], $data);
    }

    public function testEdit(): void
    {
        $tax = TaxFactory::createOne([
            'name' => 'OldTax',
            'rate' => 5.0,
            'type' => Tax::TYPE_FLAT_RATE,
        ])->_real();

        $data = $this->requestPatch(
            $this->getIriFromResource($tax),
            [
                'name' => 'NewTax',
                'rate' => 7.5,
            ]
        );

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource($tax),
            '@id' => $this->getIriFromResource($tax),
            '@type' => 'Tax',
            'id' => $tax->getId()->toString(),
            'name' => 'NewTax',
            'rate' => 7.5,
            'type' => Tax::TYPE_FLAT_RATE,
        ], $data);
    }

    public function testDelete(): void
    {
        $tax = TaxFactory::createOne()->_real();

        $this->requestDelete($this->getIriFromResource($tax));
    }

    public function testGetCollection(): void
    {
        TaxFactory::createMany(3);

        $data = $this->requestGetCollection('/api/taxes');

        self::assertArraySubset([
            '@context' => $this->getContextForResource(Tax::class),
            '@id' => '/api/taxes',
            '@type' => 'Collection',
        ], $data);
    }

    public function testCannotAccessTaxFromDifferentCompany(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $tax = TaxFactory::createOne(['company' => $otherCompany])->_real();
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $response = self::$client->request('GET', $this->getIriFromResource($tax), [
            'headers' => [
                'content-type' => 'application/ld+json',
                'accept' => 'application/ld+json',
            ],
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
