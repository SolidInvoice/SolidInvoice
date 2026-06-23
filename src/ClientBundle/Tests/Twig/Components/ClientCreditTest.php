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

namespace SolidInvoice\ClientBundle\Tests\Twig\Components;

use Brick\Math\BigInteger;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Twig\Components\ClientCredit;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;
use Zenstruck\Foundry\Test\Factories;

#[CoversClass(ClientCredit::class)]
final class ClientCreditTest extends LiveComponentTest
{
    use Factories;

    private function createCreditComponent(Client $client): TestLiveComponent
    {
        return $this->createLiveComponent(
            name: ClientCredit::class,
            data: ['client' => $client],
            client: $this->client,
        )->actingAs($this->getUser());
    }

    public function testSaveWithValidAmountAddsCredit(): void
    {
        $clientProxy = ClientFactory::createOne([
            'currencyCode' => 'USD',
            'company' => $this->company,
        ]);
        $client = $clientProxy->_real();

        self::assertEquals(BigInteger::zero(), $client->getCredit()->getValue());

        $component = $this->createCreditComponent($client);

        // Simulate submitting the form with amount = 10 (dollars)
        // 'credit' is the form block prefix / LiveProp fieldName for formValues
        $component->set('credit', ['amount' => '10']);
        $component->call('save');

        // Re-fetch via a fresh entity manager to avoid detached-entity issues
        $em = self::getContainer()->get('doctrine')->getManager();
        $refreshed = $em->find(Client::class, $client->getId());

        // 10 dollars × 100 = 1000 cents
        self::assertEquals(BigInteger::of(1000), $refreshed->getCredit()->getValue());
    }

    public function testSaveWithEmptyAmountDoesNotAddCredit(): void
    {
        $clientProxy = ClientFactory::createOne([
            'currencyCode' => 'USD',
            'company' => $this->company,
        ]);
        $client = $clientProxy->_real();

        self::assertEquals(BigInteger::zero(), $client->getCredit()->getValue());

        $component = $this->createCreditComponent($client);

        // Call save with no form data — form should be invalid (amount is required)
        $component->set('credit', []);
        $component->call('save');

        // Re-fetch via a fresh entity manager to avoid detached-entity issues
        $em = self::getContainer()->get('doctrine')->getManager();
        $refreshed = $em->find(Client::class, $client->getId());

        // Credit must remain zero
        self::assertEquals(BigInteger::zero(), $refreshed->getCredit()->getValue());
    }
}
