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

use Brick\Math\BigNumber;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Twig\Components\ClientCredit;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Zenstruck\Foundry\Test\Factories;

#[CoversClass(ClientCredit::class)]
final class ClientCreditTest extends LiveComponentTest
{
    use Factories;

    public function testSaveAddsCreditToClient(): void
    {
        $client = ClientFactory::createOne([
            'currencyCode' => 'USD',
            'company' => $this->company,
        ])->_real();

        $user = $this->getUser();

        $component = $this->createLiveComponent(
            name: ClientCredit::class,
            data: ['client' => $client],
            client: $this->client,
        )->actingAs($user);

        $creditId = $client->getCredit()->getId();
        $initialValue = $client->getCredit()->getValue();

        $component->submitForm(['credit' => ['amount' => '50']], 'save');

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        $em->clear();

        $credit = $em->find(Credit::class, $creditId);

        self::assertNotNull($credit);
        self::assertTrue(
            BigNumber::of($credit->getValue())->isEqualTo(BigNumber::of($initialValue)->plus('50')),
            'Credit value should increase by exactly 50 after saving'
        );
    }

    public function testSaveWithInvalidAmountRaisesValidationError(): void
    {
        $client = ClientFactory::createOne([
            'currencyCode' => 'USD',
            'company' => $this->company,
        ])->_real();

        $user = $this->getUser();

        $component = $this->createLiveComponent(
            name: ClientCredit::class,
            data: ['client' => $client],
            client: $this->client,
        )->actingAs($user);

        $this->expectException(UnprocessableEntityHttpException::class);

        $component->submitForm(['credit' => ['amount' => 'abc']], 'save');
    }
}
