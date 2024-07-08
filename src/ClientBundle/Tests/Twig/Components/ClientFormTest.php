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
use Symfony\UX\LiveComponent\Test\TestLiveComponent;
use Zenstruck\Foundry\Test\Factories;

/**
 * @covers \SolidInvoice\ClientBundle\Twig\Components\ClientForm
 */
final class ClientFormTest extends LiveComponentTest
{
    use Factories;

    private TestLiveComponent $component;

    protected function setUp(): void
    {
        parent::setUp();

        $this->component = $this
            ->createLiveComponent(name: ClientForm::class, client: $this->client)
            ->actingAs($this->getUser());
    }

    public function testRender(): void
    {
        $this->assertMatchesHtmlSnapshot($this->component->render()->toString());
    }

    public function testRenderWithExistingData(): void
    {
        ClientFactory::faker()->seed(123);
        $client = ClientFactory::createOne(['company' => $this->company])->object();

        $component = $this
            ->createLiveComponent(ClientForm::class, ['client' => $client])
            ->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot(
            $this->replaceChecksum($this->replaceUuid($component->render()->toString()))
        );
    }

    private function replaceChecksum(string $content): string
    {
        return preg_replace('/&quot;&#x40;checksum&quot;&#x3A;&quot;[^"]+&quot;/', '"@checksum":"checksum"', $content);
    }
}
