<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\Tests\Twig\Components;

use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\DataGridBundle\Twig\Components\DataGrid;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;
use Zenstruck\Foundry\Test\Factories;

final class DataGridTest extends LiveComponentTest
{
    use Factories;

    private TestLiveComponent $component;

    protected function setUp(): void
    {
        parent::setUp();

        $this->component = $this->createLiveComponent(
            name: DataGrid::class,
            data: [
                'name' => 'client_grid',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        ClientFactory::faker()->seed(12345);
        ClientFactory::createMany(30, ['company' => $this->company, 'archived' => null, 'status' => 'active']);
    }

    public function testRenderComponent(): void
    {
        $content = $this->component->render();
        $this->assertMatchesHtmlSnapshot($this->replaceUuid($content->toString()));
    }

    public function testRenderComponentWithData(): void
    {
        $content = $this->component->refresh()->render();
        $this->assertMatchesHtmlSnapshot($this->replaceUuid($content->toString()));
    }

    public function testComponentWithPaging(): void
    {
        $content = $this->component->refresh()->render();
        $this->assertMatchesHtmlSnapshot($this->replaceUuid($content->toString()));

        $nextPage = $this->component->set('page', 2)->render();
        $this->assertMatchesHtmlSnapshot($this->replaceUuid($nextPage->toString()));

        $nextPage = $this->component->set('page', 3)->render();
        $this->assertMatchesHtmlSnapshot($this->replaceUuid($nextPage->toString()));
    }

    public function testComponentWithSort(): void
    {
        $content = $this->component->refresh()->render();
        $this->assertMatchesHtmlSnapshot($this->replaceUuid($content->toString()));

        $nextPage = $this->component->set('sort', 'name,asc')->render();
        $this->assertMatchesHtmlSnapshot($this->replaceUuid($nextPage->toString()));

        $nextPage = $this->component->set('sort', 'created,desc')->render();
        $this->assertMatchesHtmlSnapshot($this->replaceUuid($nextPage->toString()));
    }

    private function replaceUuid(string $content): string
    {
        return preg_replace('#[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}#', '91656880-2d93-11ef-933f-5a2cf21a5680', $content);
    }
}
