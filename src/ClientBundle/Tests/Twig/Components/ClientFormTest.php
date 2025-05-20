<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Tests\Twig\Components;

use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Twig\Components\ClientForm;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Component\Uid\Ulid;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;
use Zenstruck\Foundry\Test\Factories;

/**
 * @covers \SolidInvoice\ClientBundle\Twig\Components\ClientForm
 */
final class ClientFormTest extends LiveComponentTest
{
    use Factories;

    public function testRender(): void
    {
        $component = $this
            ->createLiveComponent(name: ClientForm::class, client: $this->client)
            ->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($component->render()->toString());
    }

    public function testRenderWithExistingData(): void
    {
        $client = ClientFactory::createOne([
            'name' => 'Foo Bar',
            'vatNumber' => '12345',
            'website' => 'https://example.com',
            'currencyCode' => 'SBD',
            'company' => $this->company
        ])->_real();

        (function (): void {
            /** @var Tax $this */
            $this->id = Ulid::fromString('0f9e91e6-06ba-11ef-a331-5a2cf21a5680'); // @phpstan-ignore-line
        })(...)->call($client);

        $component = $this
            ->createLiveComponent(ClientForm::class, ['client' => $client])
            ->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot(
            $this->replaceUuid($component->render()->toString())
        );
    }
}
