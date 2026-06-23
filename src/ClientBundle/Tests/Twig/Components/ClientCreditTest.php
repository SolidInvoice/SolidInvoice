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
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Twig\Components\ClientCredit;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

#[CoversClass(ClientCredit::class)]
final class ClientCreditTest extends LiveComponentTest
{
    use Factories;

    public function testFormWrapsModal(): void
    {
        $client = ClientFactory::createOne([
            'currencyCode' => 'USD',
            'company' => $this->company,
        ])->_real();

        $html = $this
            ->createLiveComponent(
                name: ClientCredit::class,
                data: ['client' => $client],
                client: $this->client,
            )
            ->actingAs($this->getUser())
            ->render()
            ->toString();

        // The <form> element must carry data-action so that the submit event is
        // handled by the LiveComponent rather than causing a plain POST.
        // Before the fix, {{ form(form) }} was used inside the modal body which
        // rendered a self-contained <form> with no data-action; the save button
        // (type="button") sat in the modal footer outside the form element and
        // could not submit it. After the fix, form_start() wraps the modal so
        // the form element itself has data-action="live#action:prevent".
        self::assertMatchesRegularExpression(
            '/<form\b[^>]+\bdata-action="live#action:prevent"/',
            $html,
            'The <form> element must have data-action="live#action:prevent" to trigger the LiveComponent save action',
        );

        // The save button must be type="submit" (inside the form) so that
        // pressing Enter or clicking it submits the form via the live action.
        // Before the fix it was type="button" and was outside the <form> element.
        self::assertStringContainsString(
            'type="submit"',
            $html,
            'The save button must be type="submit" inside the form',
        );
    }

    public function testSaveAddsCredit(): void
    {
        $client = ClientFactory::createOne([
            'currencyCode' => 'USD',
            'company' => $this->company,
        ])->_real();

        $clientId = $client->getId();
        self::assertInstanceOf(Ulid::class, $clientId);

        $this
            ->createLiveComponent(
                name: ClientCredit::class,
                data: ['client' => $client],
                client: $this->client,
            )
            ->actingAs($this->getUser())
            // Simulate the user entering $10.00 in the amount field.
            // ComponentWithFormTrait exposes formValues under the form's block prefix ('credit').
            // ViewTransformer::reverseTransform(10) = BigDecimal::of(1000) cents.
            ->set('credit', ['amount' => 10])
            ->call('save');

        // Fetch fresh data from the database to verify the credit was persisted.
        self::getContainer()->get('doctrine')->getManager()->clear();

        $updatedClient = self::getContainer()
            ->get('doctrine')
            ->getManager()
            ->find(Client::class, $clientId);

        self::assertNotNull($updatedClient);

        $creditValue = $updatedClient->getCredit()->getValue();

        self::assertTrue(
            BigDecimal::of(1000)->isEqualTo($creditValue),
            sprintf('Expected credit of 1000 cents ($10.00), got %s', $creditValue),
        );
    }
}
