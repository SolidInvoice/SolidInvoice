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

namespace SolidInvoice\PaymentBundle\Tests\Functional\Api;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\PaymentBundle\Entity\PaymentInstruction;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentInstructionFactory;
use Symfony\Component\Uid\Ulid;

#[Group('functional')]
final class PaymentInstructionTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return PaymentInstruction::class;
    }

    public function testCreate(): void
    {
        $data = [
            'label' => 'Business account',
            'means' => 'bank_transfer',
            'accountName' => 'Jane Freelancer',
            'accountIdentifier' => 'GB29NWBK60161331926819',
        ];

        $result = $this->requestPost('/api/payment-instructions', $data);

        self::assertArrayHasKey('id', $result);
        self::assertTrue(Ulid::isValid($result['id'], Ulid::FORMAT_BASE_32));
        self::assertSame('Business account', $result['label']);
        self::assertSame('bank_transfer', $result['means']);
        self::assertSame('Jane Freelancer', $result['accountName']);
        self::assertSame('GB29NWBK60161331926819', $result['accountIdentifier']);
    }

    public function testCreateRejectsSepaCreditTransferWithoutAccountIdentifier(): void
    {
        $data = [
            'label' => 'Business account',
            'means' => 'sepa_credit_transfer',
        ];

        self::$client->request('POST', '/api/payment-instructions', [
            'json' => $data,
            'headers' => [
                'content-type' => 'application/ld+json',
                'accept' => 'application/ld+json',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testGetCollectionIsScopedByCompany(): void
    {
        PaymentInstructionFactory::createMany(2, ['company' => $this->company]);

        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        PaymentInstructionFactory::createMany(3, ['company' => $otherCompany]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $data = $this->requestGetCollection('/api/payment-instructions');

        self::assertSame(2, $data['totalItems']);
    }

    public function testCannotAccessPaymentInstructionFromDifferentCompany(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $instruction = PaymentInstructionFactory::createOne(['company' => $otherCompany]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request('GET', $this->getIriFromResource($instruction), [
            'headers' => [
                'content-type' => 'application/ld+json',
                'accept' => 'application/ld+json',
            ],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testCannotModifyPaymentInstructionFromDifferentCompany(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $instruction = PaymentInstructionFactory::createOne(['company' => $otherCompany]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request('PATCH', $this->getIriFromResource($instruction), [
            'json' => ['label' => 'Hijacked'],
            'headers' => [
                'content-type' => 'application/merge-patch+json',
                'accept' => 'application/ld+json',
            ],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testCannotDeletePaymentInstructionFromDifferentCompany(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $instruction = PaymentInstructionFactory::createOne(['company' => $otherCompany]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request('DELETE', $this->getIriFromResource($instruction), [
            'headers' => [
                'content-type' => 'application/ld+json',
                'accept' => 'application/ld+json',
            ],
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
