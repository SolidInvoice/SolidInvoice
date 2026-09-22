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

namespace SolidInvoice\CoreBundle\Tests\Twig\Extension;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\CoreBundle\Twig\Extension\StatusExtension;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AttributeExtension;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

/**
 * Every test renders through a real Twig environment. The status functions are declared with
 * AsTwigFunction attributes, so Twig builds their signature from the PHP method. A direct call
 * to the method does not use that signature and cannot see a defect in it.
 */
final class StatusExtensionTest extends TestCase
{
    /**
     * @param array<string, mixed> $context
     */
    #[DataProvider('provideSingleStatusCalls')]
    public function testASingleStatusRendersItsTranslatedLabel(string $template, array $context, string $expected): void
    {
        self::assertSame($expected, trim($this->render($template, $context)));
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>, string}>
     */
    public static function provideSingleStatusCalls(): iterable
    {
        yield 'invoice_label' => [
            '{{ invoice_label(status) }}',
            ['status' => InvoiceStatus::Paid],
            '<span class="badge bg-green text-green-fg">Translated Paid</span>',
        ];

        yield 'invoice_label with a recurring status' => [
            '{{ invoice_label(status) }}',
            ['status' => RecurringInvoiceStatus::Paused],
            '<span class="badge bg-dark text-dark-fg">Translated Paused</span>',
        ];

        yield 'quote_label' => [
            '{{ quote_label(status) }}',
            ['status' => QuoteStatus::Accepted],
            '<span class="badge bg-green text-green-fg">Translated Accepted</span>',
        ];

        yield 'payment_label' => [
            '{{ payment_label(status) }}',
            ['status' => PaymentStatus::Captured],
            '<span class="badge bg-green text-green-fg">Translated Captured</span>',
        ];

        yield 'client_label' => [
            '{{ client_label(status) }}',
            ['status' => ClientStatus::Active],
            '<span class="badge bg-green text-green-fg">Translated Active</span>',
        ];
    }

    public function testTheTooltipStaysTheSecondArgument(): void
    {
        self::assertSame(
            '<span class="badge bg-yellow text-yellow-fg"title="Waiting for the client" rel="tooltip" >Translated Pending</span>',
            trim($this->render('{{ quote_label(status, tooltip) }}', [
                'status' => QuoteStatus::Pending,
                'tooltip' => 'Waiting for the client',
            ]))
        );
    }

    /**
     * @param list<string> $expectedValues
     */
    #[DataProvider('provideNoStatusCalls')]
    public function testNoStatusReturnsTheLabelMapOfThatEnumAlone(string $template, array $expectedValues): void
    {
        self::assertSame(implode(',', $expectedValues), $this->render($template));
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function provideNoStatusCalls(): iterable
    {
        yield 'quote_label' => [
            '{{ quote_label()|keys|join(",") }}',
            ['new', 'draft', 'pending', 'accepted', 'cancelled', 'declined', 'archived'],
        ];

        yield 'payment_label' => [
            '{{ payment_label()|keys|join(",") }}',
            ['unknown', 'failed', 'suspended', 'expired', 'pending', 'cancelled', 'new', 'captured', 'authorized', 'refunded', 'credit'],
        ];

        yield 'client_label' => [
            '{{ client_label()|keys|join(",") }}',
            ['active', 'inactive', 'archived'],
        ];
    }

    public function testQuoteAndPaymentLabelsReturnDifferentMaps(): void
    {
        self::assertNotSame(
            $this->render('{{ quote_label()|keys|join(",") }}'),
            $this->render('{{ payment_label()|keys|join(",") }}')
        );
    }

    public function testNoStatusMapsEachValueToItsRenderedLabel(): void
    {
        self::assertSame(
            '<span class="badge bg-purple text-purple-fg">Translated Archived</span>',
            trim($this->render('{{ quote_label()["archived"]|raw }}'))
        );
    }

    public function testInvoiceLabelWithNoStatusMergesTheRecurringStatuses(): void
    {
        self::assertSame(
            'new,draft,pending,paid,active,overdue,cancelled,archived,complete,paused',
            $this->render('{{ invoice_label()|keys|join(",") }}')
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function render(string $template, array $context = []): string
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static fn (string $id): string => 'translated ' . substr($id, strlen('status.')));

        $views = new FilesystemLoader();
        $views->addPath(dirname(__DIR__, 3) . '/Resources/views', 'SolidInvoiceCore');

        $environment = new Environment(new ChainLoader([
            new ArrayLoader(['test.html.twig' => $template]),
            $views,
        ]));

        $environment->addExtension(new AttributeExtension(StatusExtension::class));
        $environment->addRuntimeLoader(new FactoryRuntimeLoader([
            StatusExtension::class => static fn (): StatusExtension => new StatusExtension($translator),
        ]));

        return $environment->render('test.html.twig', $context);
    }
}
