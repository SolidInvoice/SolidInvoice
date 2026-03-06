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
use SolidInvoice\ClientBundle\Search\ContactResultFormatter;
use SolidInvoice\CoreBundle\Search\QualifiedResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\ResultFormatterInterface;
use Symfony\Component\Routing\RouterInterface;

final class ContactResultFormatterTest extends TestCase
{
    private MockObject&RouterInterface $router;

    private ContactResultFormatter $formatter;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->formatter = new ContactResultFormatter($this->router);
    }

    public function testImplementsResultFormatterInterface(): void
    {
        self::assertInstanceOf(ResultFormatterInterface::class, $this->formatter);
    }

    public function testImplementsQualifiedResultFormatterInterface(): void
    {
        self::assertInstanceOf(QualifiedResultFormatterInterface::class, $this->formatter);
    }

    public function testGetIndexNameReturnsContacts(): void
    {
        self::assertSame('contacts', $this->formatter->getIndexName());
    }

    public function testGetSupportedQualifiersReturnsEmptyArray(): void
    {
        self::assertSame([], $this->formatter->getSupportedQualifiers());
    }

    public function testFormatCombinesFirstAndLastName(): void
    {
        $this->router->method('generate')->willReturn('/clients/c1');

        $hit = [
            'id' => 'contact-1',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
            'clientId' => 'c1',
        ];

        $result = $this->formatter->format($hit);

        self::assertSame('contact', $result->type);
        self::assertSame('contact-1', $result->id);
        self::assertSame('John Doe', $result->title);
        self::assertSame('john@example.com', $result->subtitle);
    }

    public function testFormatUsesEmailAsTitleWhenNameIsEmpty(): void
    {
        $this->router->method('generate')->willReturn('/clients');

        $hit = [
            'id' => 'contact-2',
            'firstName' => '',
            'lastName' => '',
            'email' => 'noreply@example.com',
        ];

        $result = $this->formatter->format($hit);

        self::assertSame('noreply@example.com', $result->title);
    }

    public function testFormatUsesEmailAsTitleWhenFirstAndLastNameAreMissing(): void
    {
        $this->router->method('generate')->willReturn('/clients');

        $hit = [
            'id' => 'contact-3',
            'email' => 'test@example.com',
        ];

        $result = $this->formatter->format($hit);

        self::assertSame('test@example.com', $result->title);
    }

    public function testFormatGeneratesClientViewUrlWhenClientIdPresent(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generate')
            ->with('_clients_view', ['id' => 'client-99'])
            ->willReturn('/clients/client-99');

        $this->formatter->format([
            'id' => 'contact-1',
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'email' => 'jane@example.com',
            'clientId' => 'client-99',
        ]);
    }

    public function testFormatGeneratesClientsIndexUrlWhenClientIdMissing(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generate')
            ->with('_clients_index')
            ->willReturn('/clients');

        $this->formatter->format([
            'id' => 'contact-1',
            'email' => 'jane@example.com',
        ]);
    }

    public function testFormatStatusIsAlwaysNull(): void
    {
        $this->router->method('generate')->willReturn('/clients');

        $result = $this->formatter->format(['id' => 'c1', 'email' => 'a@b.com']);

        self::assertNull($result->status);
    }

    public function testFormatMetaIsAlwaysNull(): void
    {
        $this->router->method('generate')->willReturn('/clients');

        $result = $this->formatter->format(['id' => 'c1', 'email' => 'a@b.com']);

        self::assertNull($result->meta);
    }

    public function testFormatWithOnlyFirstName(): void
    {
        $this->router->method('generate')->willReturn('/clients');

        $result = $this->formatter->format([
            'id' => 'c1',
            'firstName' => 'Jane',
            'email' => 'jane@example.com',
        ]);

        self::assertSame('Jane', $result->title);
    }

    public function testFormatWithOnlyLastName(): void
    {
        $this->router->method('generate')->willReturn('/clients');

        $result = $this->formatter->format([
            'id' => 'c1',
            'lastName' => 'Smith',
            'email' => 'smith@example.com',
        ]);

        self::assertSame('Smith', $result->title);
    }
}
