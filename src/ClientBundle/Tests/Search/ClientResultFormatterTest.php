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

namespace SolidInvoice\ClientBundle\Tests\Search;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Search\ClientResultFormatter;
use SolidInvoice\CoreBundle\Search\QualifiedResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\ResultFormatterInterface;
use Symfony\Component\Routing\RouterInterface;

final class ClientResultFormatterTest extends TestCase
{
    private MockObject&RouterInterface $router;

    private ClientResultFormatter $formatter;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->formatter = new ClientResultFormatter($this->router);
    }

    public function testImplementsResultFormatterInterface(): void
    {
        self::assertInstanceOf(ResultFormatterInterface::class, $this->formatter);
    }

    public function testImplementsQualifiedResultFormatterInterface(): void
    {
        self::assertInstanceOf(QualifiedResultFormatterInterface::class, $this->formatter);
    }

    public function testGetIndexNameReturnsClients(): void
    {
        self::assertSame('clients', $this->formatter->getIndexName());
    }

    public function testGetSupportedQualifiersReturnsStatusMapping(): void
    {
        self::assertSame(['status' => 'status'], $this->formatter->getSupportedQualifiers());
    }

    public function testFormatMapsHitToSearchResult(): void
    {
        $this->router
            ->method('generate')
            ->with('_clients_view', ['id' => 'client-id-1'])
            ->willReturn('/clients/client-id-1');

        $hit = [
            'id' => 'client-id-1',
            'name' => 'Acme Corp',
            'website' => 'https://acme.example.com',
            'status' => 'active',
        ];

        $result = $this->formatter->format($hit);

        self::assertSame('client', $result->type);
        self::assertSame('client-id-1', $result->id);
        self::assertSame('Acme Corp', $result->title);
        self::assertSame('https://acme.example.com', $result->subtitle);
        self::assertSame('/clients/client-id-1', $result->url);
        self::assertSame('active', $result->status);
        self::assertNull($result->meta);
    }

    public function testFormatWithMissingNameFallsBackToEmptyString(): void
    {
        $this->router->method('generate')->willReturn('/clients/id1');

        $result = $this->formatter->format(['id' => 'id1']);

        self::assertSame('', $result->title);
    }

    public function testFormatWithMissingWebsiteFallsBackToEmptyString(): void
    {
        $this->router->method('generate')->willReturn('/clients/id1');

        $result = $this->formatter->format(['id' => 'id1', 'name' => 'Acme']);

        self::assertSame('', $result->subtitle);
    }

    public function testFormatWithMissingStatusResultsInNullStatus(): void
    {
        $this->router->method('generate')->willReturn('/clients/id1');

        $result = $this->formatter->format(['id' => 'id1', 'name' => 'Acme']);

        self::assertNull($result->status);
    }

    public function testFormatGeneratesCorrectRouteWithClientId(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generate')
            ->with('_clients_view', ['id' => 'abc-123'])
            ->willReturn('/clients/abc-123');

        $this->formatter->format(['id' => 'abc-123']);
    }

    public function testMetaIsAlwaysNull(): void
    {
        $this->router->method('generate')->willReturn('/clients/id1');

        $result = $this->formatter->format(['id' => 'id1', 'name' => 'Acme', 'status' => 'active']);

        self::assertNull($result->meta);
    }
}
