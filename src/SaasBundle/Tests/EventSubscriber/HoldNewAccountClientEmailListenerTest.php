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

namespace SolidInvoice\SaasBundle\Tests\EventSubscriber;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use SolidInvoice\SaasBundle\Entity\HeldEmail;
use SolidInvoice\SaasBundle\EventSubscriber\HoldNewAccountClientEmailListener;
use SolidInvoice\SaasBundle\Service\ClientEmailRisk;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class HoldNewAccountClientEmailListenerTest extends TestCase
{
    private const string NOW = '2026-10-28 10:00';

    /**
     * @var list<HeldEmail>
     */
    private array $held = [];

    public function testHoldsARiskyEmailFromANewAccount(): void
    {
        $event = $this->send($this->email('2026-10-27', '24/7 Support: +1 813 380 2097'));

        self::assertTrue($event->isRejected());
        self::assertCount(1, $this->held);
        self::assertSame(['victim@example.com'], $this->held[0]->getRecipients());
        self::assertSame('24/7 Support: +1 813 380 2097', $this->held[0]->getSubject());
        self::assertStringContainsString('Pay now', $this->held[0]->getMessage());
    }

    public function testSendsAnOrdinaryEmailFromANewAccount(): void
    {
        $event = $this->send($this->email('2026-10-27', 'Invoice #INV-0001'));

        self::assertFalse($event->isRejected());
        self::assertSame([], $this->held);
    }

    public function testLeavesEstablishedAccountsAlone(): void
    {
        $event = $this->send($this->email('2026-09-01', '24/7 Support: +1 813 380 2097'));

        self::assertFalse($event->isRejected());
    }

    public function testDecidesOnlyWhenTheTransportDelivers(): void
    {
        $event = $this->send($this->email('2026-10-27', '24/7 Support: +1 813 380 2097'), queued: true);

        self::assertFalse($event->isRejected());
    }

    public function testHoldsPastTheDailyCap(): void
    {
        $listener = $this->listener();
        $email = $this->email('2026-10-27', 'Invoice #INV-0001');

        for ($i = 0; $i < 2; ++$i) {
            self::assertFalse($this->send($email, listener: $listener)->isRejected());
        }

        self::assertTrue($this->send($email, listener: $listener)->isRejected());
        self::assertSame(['scam_terms' => 1, 'no_payment_method' => 1, 'daily_limit' => 0], $this->held[0]->getReasons());
    }

    private function email(string $companyCreated, string $subject): InvoiceEmail
    {
        $company = new Company();
        $company->setName('Acme');
        $company->setCreated(new DateTimeImmutable($companyCreated));

        $invoice = new Invoice();
        $invoice->setCompany($company);
        $invoice->addLine(new Line()->setName('iPhone 18 Pro Max'));

        $email = new InvoiceEmail($invoice);
        $email->subject($subject)->from('acme@example.com')->to('victim@example.com')->text('Pay now');

        return $email;
    }

    private function send(Email $email, bool $queued = false, ?HoldNewAccountClientEmailListener $listener = null): MessageEvent
    {
        $event = new MessageEvent($email, Envelope::create($email), 'smtp', $queued);
        ($listener ?? $this->listener())($event);

        return $event;
    }

    private function listener(): HoldNewAccountClientEmailListener
    {
        $clock = new MockClock(new DateTimeImmutable(self::NOW));

        $repository = $this->createStub(PaymentMethodRepository::class);
        $repository->method('count')->willReturn(0);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            self::assertInstanceOf(HeldEmail::class, $entity);
            $this->held[] = $entity;
        });

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repository);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        $limiter = new RateLimiterFactory(['id' => 'test', 'policy' => 'sliding_window', 'limit' => 2, 'interval' => '1 day'], new InMemoryStorage());

        return new HoldNewAccountClientEmailListener(new ClientEmailRisk($registry, $clock), $limiter, $registry, $clock, new NullLogger());
    }
}
