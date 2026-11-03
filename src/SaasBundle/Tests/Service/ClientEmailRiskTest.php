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

namespace SolidInvoice\SaasBundle\Tests\Service;

use Carbon\CarbonImmutable;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use SolidInvoice\SaasBundle\Service\ClientEmailRisk;
use Symfony\Component\Clock\MockClock;

final class ClientEmailRiskTest extends TestCase
{
    public function testTheReportedScamIsRisky(): void
    {
        $invoice = $this->invoice('iPhone 18 Pro Max');
        $invoice->setClient(new Client()->setCreated(CarbonImmutable::parse('2026-10-28 09:30')));

        $signals = $this->risk(paymentMethods: 0)->signals($invoice, '24/7 Support: +1 813 380 2097');

        self::assertSame([
            'phone_in_subject' => 2,
            'scam_terms' => 1,
            'new_client' => 1,
            'no_payment_method' => 1,
        ], $signals);
        self::assertTrue(ClientEmailRisk::isRisky($signals));
    }

    public function testANewFreelancerWithoutAPaymentMethodIsNotRisky(): void
    {
        $invoice = $this->invoice('Logo design');
        $invoice->setClient(new Client()->setCreated(CarbonImmutable::parse('2026-10-28 09:30')));

        $signals = $this->risk(paymentMethods: 0)->signals($invoice, 'Invoice #INV-2026-0001');

        self::assertSame(['new_client' => 1, 'no_payment_method' => 1], $signals);
        self::assertFalse(ClientEmailRisk::isRisky($signals));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function phoneProvider(): iterable
    {
        yield 'international' => ['+1 813 380 2097', true];
        yield 'international, no spaces' => ['+27821234567', true];
        yield 'north american' => ['(813) 380-2097', true];
        yield 'north american, dashes' => ['813-380-2097', true];
        yield 'bank account' => ['Account 62001234567', false];
        yield 'invoice number' => ['INV-2026-0001', false];
        yield 'date' => ['2026-10-28', false];
        yield 'amount' => ['$1,299.99', false];
    }

    #[DataProvider('phoneProvider')]
    public function testPhoneNumbers(string $subject, bool $isPhone): void
    {
        $signals = $this->risk(paymentMethods: 1)->signals($this->invoice('Consulting'), $subject);

        self::assertSame($isPhone, isset($signals['phone_in_subject']));
    }

    public function testPhoneInLinesAndTerms(): void
    {
        $invoice = $this->invoice('Refund desk +44 20 7946 0958');
        $invoice->setTerms('Questions? Call (813) 380-2097');

        $signals = $this->risk(paymentMethods: 1)->signals($invoice, 'Invoice');

        self::assertSame(['phone_in_lines' => 2, 'phone_in_notes_or_terms' => 1, 'scam_terms' => 1], $signals);
    }

    private function invoice(string $line): Invoice
    {
        $invoice = new Invoice();
        $invoice->setCompany(new Company());
        $invoice->addLine(new Line()->setName($line));

        return $invoice;
    }

    private function risk(int $paymentMethods): ClientEmailRisk
    {
        $repository = $this->createStub(PaymentMethodRepository::class);
        $repository->method('count')->willReturn($paymentMethods);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repository);

        return new ClientEmailRisk($registry, new MockClock(CarbonImmutable::parse('2026-10-28 10:00')));
    }
}
