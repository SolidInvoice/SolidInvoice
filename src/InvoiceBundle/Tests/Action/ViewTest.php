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

namespace SolidInvoice\InvoiceBundle\Tests\Action;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Action\View;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

final class ViewTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use MatchesSnapshots;
    use Factories;

    private const CLIENT_ID = '01JGXKV8QZ0000000000000001';

    private const INVOICE_ID = '181aaf4a-0097-11ef-9b64-5a2cf21a5680';

    #[DataProvider('invoiceStatusProvider')]
    public function testView(InvoiceStatus $status): void
    {
        $request = Request::createFromGlobals();
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $twig = self::getContainer()->get('twig');

        $action = new View(
            self::getContainer()->get('doctrine')->getRepository(Payment::class),
            new Generator('', new NullLogger()),
            $twig
        );

        $client = ClientFactory::createOne([
            'currencyCode' => 'USD',
            'name' => 'Johnston PLC',
            'website' => 'https://www.example.com',
            'vatNumber' => 'GB123456789',
        ])->_real();
        $client->setId(Ulid::fromString(self::CLIENT_ID));

        /** @var Invoice $invoice */
        $invoice = InvoiceFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => $status,
                'total' => 100,
                'balance' => 100,
                'baseTotal' => 100,
                'created' => new DateTimeImmutable('2021-09-01'),
                'lines' => [
                    (new Line())
                        ->setDescription('Test Item')
                        ->setPrice(100)
                        ->setQty(1)
                        ->updateTotal(),
                ],
                'terms' => 'Test Terms',
                'notes' => 'Test Notes',
                'discount' => new Discount(),
                'due' => new DateTimeImmutable('2021-09-30'),
                'invoiceDate' => new DateTimeImmutable('2021-09-30'),
                'paidDate' => null,
                'tax' => 0,
            ])
            ->_real();

        $uuid = Ulid::fromString(self::INVOICE_ID);
        $invoice->setId($uuid)
            ->setUuid(Uuid::fromString(self::INVOICE_ID))
            ->setInvoiceId('INV-2021-0001')
        ;

        $params = $action($request, $invoice);

        $response = $twig->resolveTemplate('@SolidInvoiceInvoice/Default/view.html.twig')->renderBlock('content', $params);

        $this->assertMatchesHtmlSnapshot($response);
    }

    public function testViewWithPayments(): void
    {
        $request = Request::createFromGlobals();
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        self::getContainer()->get('security.token_storage');

        $twig = self::getContainer()->get('twig');

        $action = new View(
            self::getContainer()->get('doctrine')->getRepository(Payment::class),
            new Generator('', new NullLogger()),
            $twig
        );

        $client = ClientFactory::createOne([
            'currencyCode' => 'USD',
            'name' => 'Johnston PLC',
            'website' => 'https://www.example.com',
            'vatNumber' => 'GB123456789',
        ])->_real();
        $client->setId(Ulid::fromString(self::CLIENT_ID));

        /** @var Invoice $invoice */
        $invoice = InvoiceFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => InvoiceStatus::Paid,
                'total' => 100,
                'balance' => 100,
                'baseTotal' => 100,
                'created' => new DateTimeImmutable('2021-09-01'),
                'lines' => [
                    (new Line())
                        ->setDescription('Test Item')
                        ->setPrice(100)
                        ->setQty(1)
                        ->updateTotal(),
                ],
                'terms' => 'Test Terms',
                'notes' => 'Test Notes',
                'discount' => new Discount(),
                'due' => new DateTimeImmutable('2021-09-30'),
                'invoiceDate' => new DateTimeImmutable('2021-09-30'),
                'paidDate' => null,
                'tax' => 0,
            ])
            ->_real();

        $payment = new Payment();
        $payment->setTotalAmount(100);
        $payment->setMethod((new PaymentMethod())->setName('Credit Card'));
        $payment->setStatus(PaymentStatus::Captured);
        $payment->setCurrencyCode('USD');
        $invoice->addPayment($payment);

        $uuid = Ulid::fromString(self::INVOICE_ID);
        $invoice->setId($uuid)
            ->setUuid(Uuid::fromString(self::INVOICE_ID))
            ->setInvoiceId('INV-2021-0001')
            ->updateLines()
        ;

        $params = $action($request, $invoice);

        $response = $twig->resolveTemplate('@SolidInvoiceInvoice/Default/view.html.twig')->renderBlock('content', $params);

        $this->assertMatchesHtmlSnapshot($response);
    }

    /**
     * @return iterable<array{0: InvoiceStatus}>
     */
    public static function invoiceStatusProvider(): iterable
    {
        foreach (InvoiceStatus::cases() as $status) {
            if ($status !== InvoiceStatus::New) {
                yield "Status {$status->value}" => [$status];
            }
        }
    }

    public function testViewWithDiscount(): void
    {
        $request = Request::createFromGlobals();
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $twig = self::getContainer()->get('twig');

        $action = new View(
            self::getContainer()->get('doctrine')->getRepository(Payment::class),
            new Generator('', new NullLogger()),
            $twig
        );

        $client = ClientFactory::createOne([
            'currencyCode' => 'USD',
            'name' => 'Johnston PLC',
            'website' => 'https://www.example.com',
            'vatNumber' => 'GB123456789',
        ])->_real();
        $client->setId(Ulid::fromString(self::CLIENT_ID));

        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(10);

        /** @var Invoice $invoice */
        $invoice = InvoiceFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => InvoiceStatus::Pending,
                'total' => 90,
                'balance' => 90,
                'baseTotal' => 100,
                'created' => new DateTimeImmutable('2021-09-01'),
                'lines' => [
                    (new Line())
                        ->setDescription('Test Item')
                        ->setPrice(100)
                        ->setQty(1)
                        ->updateTotal(),
                ],
                'terms' => 'Test Terms',
                'notes' => 'Test Notes',
                'discount' => $discount,
                'due' => new DateTimeImmutable('2021-09-30'),
                'invoiceDate' => new DateTimeImmutable('2021-09-30'),
                'paidDate' => null,
                'tax' => 0,
            ])
            ->_real();

        $uuid = Ulid::fromString(self::INVOICE_ID);
        $invoice->setId($uuid)
            ->setUuid(Uuid::fromString(self::INVOICE_ID))
            ->setInvoiceId('INV-2021-0001')
        ;

        $params = $action($request, $invoice);

        $response = $twig->resolveTemplate('@SolidInvoiceInvoice/Default/view.html.twig')->renderBlock('content', $params);

        $this->assertMatchesHtmlSnapshot($response);
    }

    public function testViewWithTax(): void
    {
        $request = Request::createFromGlobals();
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $twig = self::getContainer()->get('twig');

        $action = new View(
            self::getContainer()->get('doctrine')->getRepository(Payment::class),
            new Generator('', new NullLogger()),
            $twig
        );

        $client = ClientFactory::createOne([
            'currencyCode' => 'USD',
            'name' => 'Johnston PLC',
            'website' => 'https://www.example.com',
            'vatNumber' => 'GB123456789',
        ])->_real();
        $client->setId(Ulid::fromString(self::CLIENT_ID));

        /** @var Invoice $invoice */
        $invoice = InvoiceFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => InvoiceStatus::Pending,
                'total' => 115,
                'balance' => 115,
                'baseTotal' => 100,
                'created' => new DateTimeImmutable('2021-09-01'),
                'lines' => [
                    (new Line())
                        ->setDescription('Test Item with Tax')
                        ->setPrice(100)
                        ->setQty(1)
                        ->updateTotal(),
                ],
                'terms' => 'Test Terms',
                'notes' => 'Test Notes',
                'discount' => new Discount(),
                'due' => new DateTimeImmutable('2021-09-30'),
                'invoiceDate' => new DateTimeImmutable('2021-09-30'),
                'paidDate' => null,
                'tax' => 15,
            ])
            ->_real();

        $uuid = Ulid::fromString(self::INVOICE_ID);
        $invoice->setId($uuid)
            ->setUuid(Uuid::fromString(self::INVOICE_ID))
            ->setInvoiceId('INV-2021-0001')
        ;

        $params = $action($request, $invoice);

        $response = $twig->resolveTemplate('@SolidInvoiceInvoice/Default/view.html.twig')->renderBlock('content', $params);

        $this->assertMatchesHtmlSnapshot($response);
    }

    public function testViewWithRelatedQuote(): void
    {
        $request = Request::createFromGlobals();
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $twig = self::getContainer()->get('twig');

        $action = new View(
            self::getContainer()->get('doctrine')->getRepository(Payment::class),
            new Generator('', new NullLogger()),
            $twig
        );

        $client = ClientFactory::createOne([
            'currencyCode' => 'USD',
            'name' => 'Johnston PLC',
            'website' => 'https://www.example.com',
            'vatNumber' => 'GB123456789',
        ])->_real();
        $client->setId(Ulid::fromString(self::CLIENT_ID));

        // Create a related quote
        $quote = new Quote();
        $quoteUuid = Ulid::fromString('281aaf4a-0097-11ef-9b64-5a2cf21a5680');
        $quote->setId($quoteUuid)
            ->setStatus(QuoteStatus::Accepted)
            ->setClient($client);
        $quote->setQuoteId('QUOT-2021-0001');

        /** @var Invoice $invoice */
        $invoice = InvoiceFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => InvoiceStatus::Pending,
                'total' => 100,
                'balance' => 100,
                'baseTotal' => 100,
                'created' => new DateTimeImmutable('2021-09-01'),
                'lines' => [
                    (new Line())
                        ->setDescription('Test Item')
                        ->setPrice(100)
                        ->setQty(1)
                        ->updateTotal(),
                ],
                'terms' => 'Test Terms',
                'notes' => 'Test Notes',
                'discount' => new Discount(),
                'due' => new DateTimeImmutable('2021-09-30'),
                'invoiceDate' => new DateTimeImmutable('2021-09-30'),
                'paidDate' => null,
                'tax' => 0,
            ])
            ->_real();

        $uuid = Ulid::fromString(self::INVOICE_ID);
        $invoice->setId($uuid)
            ->setUuid(Uuid::fromString(self::INVOICE_ID))
            ->setInvoiceId('INV-2021-0001')
            ->setQuote($quote)
        ;

        $params = $action($request, $invoice);

        $response = $twig->resolveTemplate('@SolidInvoiceInvoice/Default/view.html.twig')->renderBlock('content', $params);

        $this->assertMatchesHtmlSnapshot($response);
    }

    public function testViewWithClientContacts(): void
    {
        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        self::getContainer()
            ->set('security.csrf.token_manager', $csrfTokenManager);

        $csrfTokenManager
            ->method('getToken')
            ->with('send_manual_reminder')
            ->willReturn(new CsrfToken('send_manual_reminder', 'send_manual_reminder'));

        $request = Request::createFromGlobals();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $twig = self::getContainer()->get('twig');

        $action = new View(
            self::getContainer()->get('doctrine')->getRepository(Payment::class),
            new Generator('', new NullLogger()),
            $twig
        );

        $contact = new Contact();
        $contact->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('john.doe@example.com');

        $client = ClientFactory::createOne([
            'currencyCode' => 'USD',
            'name' => 'Johnston PLC',
            'website' => 'https://www.example.com',
            'vatNumber' => 'GB123456789',
        ])->_real();
        $client->setId(Ulid::fromString(self::CLIENT_ID));

        /** @var Invoice $invoice */
        $invoice = InvoiceFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => InvoiceStatus::Pending,
                'total' => 100,
                'balance' => 100,
                'baseTotal' => 100,
                'created' => new DateTimeImmutable('2021-09-01'),
                'lines' => [
                    (new Line())
                        ->setDescription('Test Item')
                        ->setPrice(100)
                        ->setQty(1)
                        ->updateTotal(),
                ],
                'terms' => 'Test Terms',
                'notes' => 'Test Notes',
                'discount' => new Discount(),
                'due' => new DateTimeImmutable('2021-09-30'),
                'invoiceDate' => new DateTimeImmutable('2021-09-30'),
                'paidDate' => null,
                'tax' => 0,
                'users' => [$contact],
            ])
            ->_real();

        $uuid = Ulid::fromString(self::INVOICE_ID);
        $invoice->setId($uuid)
            ->setUuid(Uuid::fromString(self::INVOICE_ID))
            ->setInvoiceId('INV-2021-0001')
        ;

        $params = $action($request, $invoice);

        $response = $twig->resolveTemplate('@SolidInvoiceInvoice/Default/view.html.twig')->renderBlock('content', $params);

        $this->assertMatchesHtmlSnapshot($response);
    }

    public function testViewWithPartialPayment(): void
    {
        $request = Request::createFromGlobals();
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $twig = self::getContainer()->get('twig');

        $action = new View(
            self::getContainer()->get('doctrine')->getRepository(Payment::class),
            new Generator('', new NullLogger()),
            $twig
        );

        $client = ClientFactory::createOne([
            'currencyCode' => 'USD',
            'name' => 'Johnston PLC',
            'website' => 'https://www.example.com',
            'vatNumber' => 'GB123456789',
        ])->_real();
        $client->setId(Ulid::fromString(self::CLIENT_ID));

        /** @var Invoice $invoice */
        $invoice = InvoiceFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => InvoiceStatus::Pending,
                'total' => 100,
                'balance' => 50,
                'baseTotal' => 100,
                'created' => new DateTimeImmutable('2021-09-01'),
                'lines' => [
                    (new Line())
                        ->setDescription('Test Item')
                        ->setPrice(100)
                        ->setQty(1)
                        ->updateTotal(),
                ],
                'terms' => 'Test Terms',
                'notes' => 'Test Notes',
                'discount' => new Discount(),
                'due' => new DateTimeImmutable('2021-09-30'),
                'invoiceDate' => new DateTimeImmutable('2021-09-30'),
                'paidDate' => null,
                'tax' => 0,
            ])
            ->_real();

        $payment = new Payment();
        $payment->setTotalAmount(50);
        $payment->setMethod((new PaymentMethod())->setName('Credit Card'));
        $payment->setStatus(PaymentStatus::Captured);
        $payment->setCurrencyCode('USD');
        $invoice->addPayment($payment);

        $uuid = Ulid::fromString(self::INVOICE_ID);
        $invoice->setId($uuid)
            ->setUuid(Uuid::fromString(self::INVOICE_ID))
            ->setInvoiceId('INV-2021-0001')
            ->updateLines()
        ;

        $params = $action($request, $invoice);

        $response = $twig->resolveTemplate('@SolidInvoiceInvoice/Default/view.html.twig')->renderBlock('content', $params);

        $this->assertMatchesHtmlSnapshot($response);
    }
}
