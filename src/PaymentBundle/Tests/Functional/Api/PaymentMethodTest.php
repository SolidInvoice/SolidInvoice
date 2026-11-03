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
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;
use Symfony\Component\HttpFoundation\Response;
use function array_column;

#[Group('functional')]
final class PaymentMethodTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return PaymentMethod::class;
    }

    public function testListsTheCompanysMethods(): void
    {
        PaymentMethodFactory::createOne([
            'name' => 'Stripe',
            'gatewayName' => 'stripe_checkout',
            'factoryName' => 'stripe_checkout',
            'internal' => false,
            'enabled' => true,
            'config' => ['secret_key' => 'sk_live_secret'],
        ]);

        $data = $this->requestGetCollection('/api/payment-methods');
        $methods = array_column($data['member'], null, 'gatewayName');

        self::assertEqualsCanonicalizing(['cash', 'bank_transfer', 'credit', 'cheque', 'card_terminal', 'stripe_checkout'], array_keys($methods));

        self::assertSame([
            'name' => 'Cheque',
            'gatewayName' => 'cheque',
            'factoryName' => 'offline',
            'internal' => true,
            'enabled' => true,
            'online' => false,
        ], array_diff_key($methods['cheque'], ['@id' => true, '@type' => true]));

        self::assertSame('Card terminal', $methods['card_terminal']['name']);
        self::assertTrue($methods['stripe_checkout']['online']);
        self::assertStringNotContainsString('sk_live_secret', (string) json_encode($data));
        self::assertArrayNotHasKey('config', $methods['stripe_checkout']);
    }

    public function testGetsOneMethod(): void
    {
        $method = PaymentMethodFactory::createOne(['gatewayName' => 'mpesa', 'factoryName' => 'offline', 'internal' => false, 'enabled' => false]);

        $data = $this->requestGet($this->getIriFromResource($method));

        self::assertSame('mpesa', $data['gatewayName']);
        self::assertFalse($data['enabled']);
        self::assertFalse($data['online']);
    }

    public function testAnotherCompanysMethodsAreNotVisible(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreign = PaymentMethodFactory::createOne(['gatewayName' => 'foreign_only', 'company' => $otherCompany]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $data = $this->requestGetCollection('/api/payment-methods');

        self::assertNotContains('foreign_only', array_column($data['member'], 'gatewayName'));
        self::assertSame(5, $data['totalItems']);

        self::$client->request('GET', $this->getIriFromResource($foreign), ['headers' => ['accept' => 'application/ld+json']]);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
