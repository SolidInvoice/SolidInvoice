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

namespace SolidInvoice\QuoteBundle\Tests\Listener\Mailer;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Contracts\PaidSubscriptionGateInterface;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\CoreBundle\Templates\BillingTemplateRegistry;
use SolidInvoice\CoreBundle\Templates\BillingTemplateResolver;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Listener\Mailer\QuotePdfListener;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Part\DataPart;
use Twig\Environment;

final class QuotePdfListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testListener(): void
    {
        $quote = new Quote();

        $mailer = M::mock(MailerInterface::class);
        $mailer->shouldReceive('send');

        $twig = M::mock(Environment::class);
        $twig->shouldReceive('render')
            ->once()
            ->with('@SolidInvoiceQuote/Pdf/quote.html.twig', ['quote' => $quote])
            ->andReturn('<p>Quote #1</p>');

        $pdf = M::mock(Generator::class);
        $pdf->shouldReceive('canPrintPdf')
            ->andReturnTrue();

        $pdf->shouldReceive('generate')
            ->with('<p>Quote #1</p>')
            ->andReturn('PDF: Quote #1');

        $listener = new QuotePdfListener($pdf, $twig, $this->createTemplateResolver());

        $message = new QuoteEmail($quote);
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        self::assertEquals(
            [new DataPart('PDF: Quote #1', sprintf('quote_%s.pdf', $quote->getId()), 'application/pdf')],
            $message->getAttachments()
        );
    }

    public function testEvents(): void
    {
        self::assertSame([MessageEvent::class], \array_keys(QuotePdfListener::getSubscribedEvents()));
    }

    /**
     * A resolver with the SaaS toggle off, which always resolves the built-in
     * default template.
     */
    private function createTemplateResolver(): BillingTemplateResolver
    {
        $toggle = M::mock(ToggleInterface::class);
        $toggle->allows('isActive')->with('saas_enabled')->andReturnFalse();

        return new BillingTemplateResolver(
            new BillingTemplateRegistry([]),
            M::mock(SystemConfig::class),
            M::mock(FeatureGate::class),
            M::mock(PaidSubscriptionGateInterface::class),
            $toggle,
        );
    }
}
