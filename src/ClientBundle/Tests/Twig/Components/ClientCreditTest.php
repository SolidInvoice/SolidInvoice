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

use Brick\Math\BigDecimal;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Twig\Components\ClientCredit;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Zenstruck\Foundry\Test\Factories;

#[CoversClass(ClientCredit::class)]
final class ClientCreditTest extends LiveComponentTest
{
    use DoctrineTestTrait;
    use Factories;

    public function testSaveAddsCreditToClient(): void
    {
        [$clientEntity, $component] = $this->createClientComponent();

        $creditId = $clientEntity->getCredit()->getId();
        $initialValue = $clientEntity->getCredit()->getValue();

        $component->submitForm(['credit' => ['amount' => '50']], 'save');

        $this->em->clear();
        $credit = $this->em->find(Credit::class, $creditId);

        self::assertNotNull($credit);
        self::assertSame(
            (string) BigDecimal::of($initialValue)->plus('5000'),
            (string) $credit->getValue()
        );
    }

    public function testSaveWithInvalidAmountRaisesValidationError(): void
    {
        [, $component] = $this->createClientComponent();

        $this->expectException(UnprocessableEntityHttpException::class);

        $component->submitForm(['credit' => ['amount' => 'abc']], 'save');
    }

    /**
     * @return array{0: Client, 1: mixed}
     */
    private function createClientComponent(): array
    {
        $clientEntity = ClientFactory::createOne([
            'currencyCode' => 'USD',
            'company' => $this->company,
        ])->_real();

        $component = $this->createLiveComponent(
            name: ClientCredit::class,
            data: ['client' => $clientEntity],
            client: $this->client,
        )->actingAs($this->getUser());

        return [$clientEntity, $component];
    }
}
