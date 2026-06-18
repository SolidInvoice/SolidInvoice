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

use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Twig\Components\ClientForm;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

#[CoversClass(ClientForm::class)]
final class ClientFormTest extends LiveComponentTest
{
    use Factories;

    public function testRender(): void
    {
        $component = $this
            ->createLiveComponent(name: ClientForm::class, client: $this->client)
            ->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($component->render()->toString()));
    }

    public function testRenderWithExistingData(): void
    {
        $user = $this->getUser();

        $client = ClientFactory::createOne([
            'name' => 'Foo Bar',
            'website' => 'https://example.com',
            'currencyCode' => 'SBD',
            'company' => $this->company
        ])->_real();

        $client->setId(Ulid::fromString('0f9e91e6-06ba-11ef-a331-5a2cf21a5680'));

        $component = $this
            ->createLiveComponent(ClientForm::class, ['client' => $client])
            ->actingAs($user);

        $this->assertMatchesHtmlSnapshot(
            $this->replaceUuid(
                $this->replaceChecksum($component->render()->toString())
            )
        );
    }
}
