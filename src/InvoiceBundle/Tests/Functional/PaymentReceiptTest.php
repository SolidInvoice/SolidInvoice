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

namespace SolidInvoice\InvoiceBundle\Tests\Functional;

use Brick\Math\BigInteger;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Test\Snapshot\MatchesStableHtmlSnapshot;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Manager\ReceiptManager;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Config\ConfigProvider;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Event\PaymentCompleteEvent;
use SolidInvoice\PaymentBundle\Event\PaymentEvents;
use SolidInvoice\PaymentBundle\Manager\ManualPaymentManager;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Mailer\Test\InteractsWithMailer;

#[Group('functional')]
final class PaymentReceiptTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use InteractsWithMailer;
    use MatchesSnapshots;
    use MatchesStableHtmlSnapshot {
        MatchesStableHtmlSnapshot::assertMatchesHtmlSnapshot insteadof MatchesSnapshots;
    }

    private const string INVOICE_UUID = '181aaf4a-0097-11ef-9b64-5a2cf21a5680';

    private KernelBrowser $browser;

    private Invoice $invoice;

    private PaymentMethod $method;

    protected function setUp(): void
    {
        parent::setUp();

        self::ensureKernelShutdown();
        $this->browser = self::createClient();
        $this->browser->disableReboot();
        $this->browser->loginUser(UserFactory::createOne(['companies' => [$this->company]]));
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD', 'name' => 'Acme Corporation']);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company, 'firstName' => 'Jane', 'lastName' => 'Doe', 'email' => 'jane@example.com']);

        $this->invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'total' => 150000,
            'balance' => 150000,
            'baseTotal' => 150000,
            'tax' => 0,
            'discount' => new Discount()->setType(null),
            'invoiceDate' => new DateTimeImmutable('2024-01-15', new DateTimeZone('UTC')),
            'users' => [$contact],
            'lines' => [new Line()->setDescription('Consulting')->setPrice(150000)->setQty(1)->updateTotal()],
        ]);
        $this->invoice->setUuid(Uuid::fromString(self::INVOICE_UUID))->setInvoiceId('INV-2024-001');

        $this->method = PaymentMethodFactory::createOne([
            'company' => $this->company,
            'name' => 'Bank Transfer',
            'gatewayName' => 'receipt_test',
            'factoryName' => 'offline',
            'enabled' => true,
            'internal' => false,
        ]);

        self::getContainer()->get(EntityManagerInterface::class)->flush();
        $this->mailer()->reset();
    }

    public function testThePaymentPageOffersTheReceipt(): void
    {
        $payment = $this->createPayment(PaymentStatus::Captured, 50000);

        $crawler = $this->browser->request(Request::METHOD_GET, '/invoices/payments/' . $payment->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'INV-2024-001-1');
        self::assertCount(1, $crawler->filter('a[href="/invoices/payments/' . $payment->getId() . '/receipt/download"]'));
        self::assertCount(1, $crawler->filter('form[action="/invoices/payments/' . $payment->getId() . '/receipt/send"]'));
    }

    public function testTheReceiptPdfIsServedInlineOrAsADownload(): void
    {
        $payment = $this->createPayment(PaymentStatus::Captured, 50000);

        $this->browser->request(Request::METHOD_GET, '/invoices/payments/' . $payment->getId() . '/receipt.pdf');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
        self::assertResponseHeaderSame('Content-Disposition', 'inline; filename=receipt_INV-2024-001-1.pdf');
        self::assertStringStartsWith('%PDF', (string) $this->browser->getResponse()->getContent());

        $this->browser->request(Request::METHOD_GET, '/invoices/payments/' . $payment->getId() . '/receipt/download');

        self::assertResponseHeaderSame('Content-Disposition', 'attachment; filename=receipt_INV-2024-001-1.pdf');
    }

    public function testAPaymentThatWasNotReceivedHasNoReceipt(): void
    {
        $payment = $this->createPayment(PaymentStatus::Failed, 50000);

        $this->browser->request(Request::METHOD_GET, '/invoices/payments/' . $payment->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('form[action$="/receipt/send"]');

        $this->browser->request(Request::METHOD_GET, '/invoices/payments/' . $payment->getId() . '/receipt.pdf');
        self::assertResponseStatusCodeSame(404);
    }

    public function testSendingTheReceiptEmailsTheInvoiceContactsWithThePdf(): void
    {
        $payment = $this->createPayment(PaymentStatus::Captured, 50000);

        $crawler = $this->browser->request(Request::METHOD_GET, '/invoices/payments/' . $payment->getId());
        $this->browser->submit($crawler->filter('form[action="/invoices/payments/' . $payment->getId() . '/receipt/send"]')->form());

        self::assertResponseRedirects('/invoices/payments/' . $payment->getId());
        $this->mailer()->assertSentEmailCount(1);
        $this->mailer()->sentEmails()->first()
            ->assertTo('jane@example.com')
            ->assertSubject('Payment receipt INV-2024-001-1')
            ->assertHasFile('receipt_INV-2024-001-1.pdf');
    }

    public function testReceiptNumbersFollowTheOrderOfTheInvoicePayments(): void
    {
        $failed = $this->createPayment(PaymentStatus::Failed, 10000);
        $first = $this->createPayment(PaymentStatus::Captured, 50000);
        $second = $this->createPayment(PaymentStatus::Captured, 40000);

        self::assertSame('INV-2024-001-1', $failed->getReceiptNumber());
        self::assertSame('INV-2024-001-2', $first->getReceiptNumber());
        self::assertSame('INV-2024-001-3', $second->getReceiptNumber());
    }

    public function testTheReceiptsListShowsOnlyReceivedPayments(): void
    {
        $this->createPayment(PaymentStatus::Captured, 50000, 'REF-CAPTURED');
        $this->createPayment(PaymentStatus::Failed, 10000, 'REF-FAILED');

        $this->browser->request(Request::METHOD_GET, '/invoices/receipts');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'REF-CAPTURED');
        self::assertSelectorTextNotContains('body', 'REF-FAILED');
    }

    public function testCapturingAPaymentSendsAReceipt(): void
    {
        $this->capture(true);

        $this->mailer()->assertSentEmailCount(1);
        $this->mailer()->sentEmails()->first()->assertTo('jane@example.com')->assertHasFile('receipt_INV-2024-001-1.pdf');
    }

    public function testCapturingAPaymentWithTheReceiptUntickedSendsNothing(): void
    {
        $this->capture(false);

        $this->mailer()->sentEmails()->whereTo('jane@example.com')->assertNone();
    }

    public function testARecordedPaymentSendsAReceiptAutomatically(): void
    {
        $payment = self::getContainer()->get(ManualPaymentManager::class)->record($this->invoice, BigInteger::of(10000), method: 'receipt_test');

        $this->mailer()->assertSentEmailCount(1);
        $this->mailer()->sentEmails()->first()->assertTo('jane@example.com')->assertHasFile('receipt_INV-2024-001-1.pdf');
        self::assertNotNull($payment->getReceiptSentAt());
    }

    public function testNoReceiptIsSentAutomaticallyWhenTheSettingIsOff(): void
    {
        self::getContainer()->get(SystemConfig::class)->set(ConfigProvider::RECEIPT_AUTO_SEND, '0');

        self::getContainer()->get(ManualPaymentManager::class)->record($this->invoice, BigInteger::of(10000), method: 'receipt_test');

        $this->mailer()->sentEmails()->whereTo('jane@example.com')->assertNone();

        $crawler = $this->browser->request(Request::METHOD_GET, '/payments/create/' . $this->invoice->getUuid());
        self::assertCount(0, $crawler->filter('#payment_send_receipt[checked]'), 'The capture form follows the setting.');
    }

    public function testACompletionSeenTwiceSendsOneReceipt(): void
    {
        $payment = $this->createPayment(PaymentStatus::Captured, 50000);
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);

        $dispatcher->dispatch(new PaymentCompleteEvent($payment), PaymentEvents::PAYMENT_COMPLETE);
        $dispatcher->dispatch(new PaymentCompleteEvent($payment), PaymentEvents::PAYMENT_COMPLETE);

        $this->mailer()->assertSentEmailCount(1);
    }

    public function testTheReceiptPdfMatchesTheSnapshot(): void
    {
        $payment = $this->createPayment(PaymentStatus::Captured, 50000, 'TRX-12345');

        $html = self::getContainer()->get(ReceiptManager::class)->html($payment);

        // The stylesheet is the invoice PDF's, which has its own coverage.
        $this->assertMatchesHtmlSnapshot((string) preg_replace('#<style[^>]*>.*?</style>#s', '', $html));
    }

    public function testTheReceiptEmailMatchesTheSnapshot(): void
    {
        $payment = $this->createPayment(PaymentStatus::Captured, 50000, 'TRX-12345');

        $html = self::getContainer()->get('twig')->render('@SolidInvoiceInvoice/Email/receipt.html.twig', ['payment' => $payment, 'invoice' => $this->invoice]);

        $this->assertMatchesHtmlSnapshot($html);
    }

    private function createPayment(PaymentStatus $status, int $amount, ?string $reference = null): Payment
    {
        $payment = new Payment();
        $payment->setStatus($status);
        $payment->setMethod($this->method);
        $payment->setTotalAmount($amount);
        $payment->setCurrencyCode('USD');
        $payment->setDescription('');
        $payment->setClient($this->invoice->getClient());
        $payment->setReference($reference);
        $payment->setCompleted(new DateTimeImmutable('2024-02-01 10:00:00', new DateTimeZone('UTC')));
        $payment->setCompany($this->invoice->getCompany());

        $this->invoice->addPayment($payment);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($payment);
        $entityManager->flush();

        return $payment;
    }

    private function capture(bool $sendReceipt): void
    {
        $crawler = $this->browser->request(Request::METHOD_GET, '/payments/create/' . $this->invoice->getUuid());
        $this->browser->submit($crawler->filter('form[name="payment"]')->form(), [
            'payment[payment_method]' => (string) $this->method->getId(),
            'payment[amount]' => '100.00',
            'payment[capture_online]' => false,
            'payment[send_receipt]' => $sendReceipt,
        ]);

        self::assertResponseRedirects();
    }
}
