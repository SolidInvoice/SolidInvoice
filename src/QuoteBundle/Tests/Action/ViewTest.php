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

namespace SolidInvoice\QuoteBundle\Tests\Action;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\QuoteBundle\Action\View;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Twig\Environment;
use Zenstruck\Foundry\Test\Factories;

final class ViewTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use MatchesSnapshots;
    use Factories;

    private const CLIENT_ID = '01JGXKV8QZ0000000000000001';

    private const QUOTE_ID = '181aaf4a-0097-11ef-9b64-5a2cf21a5680';

    #[DataProvider('quoteStatusProvider')]
    public function testView(QuoteStatus $status): void
    {
        $request = Request::createFromGlobals();
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $twig = self::getContainer()->get(Environment::class);

        $action = new View(
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

        /** @var Quote $quote */
        $quote = QuoteFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => $status,
                'total' => '100.00',
                'baseTotal' => '100.00',
                'created' => new DateTimeImmutable('2021-09-01'),
                'lines' => [
                    (new Line())
                        ->setDescription('Test Line')
                        ->setPrice('100.00')
                        ->setQty(1)
                        ->updateTotal(),
                ],
                'terms' => 'Test Terms',
                'notes' => 'Test Notes',
                'discount' => new Discount(),
                'tax' => 0,
            ])
            ->_real();

        $uuid = Ulid::fromString(self::QUOTE_ID);
        $quote->setId($uuid)
            ->setUuid(Uuid::fromString(self::QUOTE_ID));
        $quote->setQuoteId('QUOT-2021-0001');

        $params = $action($request, $quote);

        $response = $twig->resolveTemplate('@SolidInvoiceQuote/Default/view.html.twig')->renderBlock('content', $params);

        $this->assertMatchesHtmlSnapshot($response);
    }

    /**
     * @return iterable<array{0: QuoteStatus}>
     */
    public static function quoteStatusProvider(): iterable
    {
        foreach (QuoteStatus::cases() as $status) {
            if ($status !== QuoteStatus::New) {
                yield "Status {$status->value}" => [$status];
            }
        }
    }

    public function testViewWithDiscount(): void
    {
        $request = Request::createFromGlobals();
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $twig = self::getContainer()->get(Environment::class);

        $action = new View(
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

        /** @var Quote $quote */
        $quote = QuoteFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => QuoteStatus::Pending,
                'total' => '90.00',
                'baseTotal' => '100.00',
                'created' => new DateTimeImmutable('2021-09-01'),
                'lines' => [
                    (new Line())
                        ->setDescription('Test Line')
                        ->setPrice('100.00')
                        ->setQty(1)
                        ->updateTotal(),
                ],
                'terms' => 'Test Terms',
                'notes' => 'Test Notes',
                'discount' => $discount,
                'tax' => 0,
            ])
            ->_real();

        $uuid = Ulid::fromString(self::QUOTE_ID);
        $quote->setId($uuid)
            ->setUuid(Uuid::fromString(self::QUOTE_ID));
        $quote->setQuoteId('QUOT-2021-0001');

        $params = $action($request, $quote);

        $response = $twig->resolveTemplate('@SolidInvoiceQuote/Default/view.html.twig')->renderBlock('content', $params);

        $this->assertMatchesHtmlSnapshot($response);
    }

    public function testViewWithTax(): void
    {
        $request = Request::createFromGlobals();
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $twig = self::getContainer()->get(Environment::class);

        $action = new View(
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

        /** @var Quote $quote */
        $quote = QuoteFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => QuoteStatus::Pending,
                'total' => '115.00',
                'baseTotal' => '100.00',
                'created' => new DateTimeImmutable('2021-09-01'),
                'lines' => [
                    (new Line())
                        ->setDescription('Test Line with Tax')
                        ->setPrice('100.00')
                        ->setQty(1)
                        ->updateTotal(),
                ],
                'terms' => 'Test Terms',
                'notes' => 'Test Notes',
                'discount' => new Discount(),
                'tax' => '15.00',
            ])
            ->_real();

        $uuid = Ulid::fromString(self::QUOTE_ID);
        $quote->setId($uuid)
            ->setUuid(Uuid::fromString(self::QUOTE_ID));
        $quote->setQuoteId('QUOT-2021-0001');

        $params = $action($request, $quote);

        $response = $twig->resolveTemplate('@SolidInvoiceQuote/Default/view.html.twig')->renderBlock('content', $params);

        $this->assertMatchesHtmlSnapshot($response);
    }

    public function testViewWithRelatedInvoice(): void
    {
        $request = Request::createFromGlobals();
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $twig = self::getContainer()->get(Environment::class);

        $action = new View(
            new Generator('', new NullLogger()),
            $twig
        );

        /** @var Client $client */
        $client = ClientFactory::createOne([
            'currencyCode' => 'USD',
            'name' => 'Johnston PLC',
            'website' => 'https://www.example.com',
            'vatNumber' => 'GB123456789',
        ])->_real();
        $client->setId(Ulid::fromString(self::CLIENT_ID));

        // Create a related invoice
        $invoice = new Invoice();
        $invoiceUuid = Ulid::fromString('281aaf4a-0097-11ef-9b64-5a2cf21a5680');
        $invoice
            ->setClient($client)
            ->setId($invoiceUuid)
            ->setInvoiceId('INV-2021-0001')
            ->setStatus(InvoiceStatus::Pending);

        /** @var Quote $quote */
        $quote = QuoteFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => QuoteStatus::Accepted,
                'total' => '100.00',
                'baseTotal' => '100.00',
                'created' => new DateTimeImmutable('2021-09-01'),
                'lines' => [
                    (new Line())
                        ->setDescription('Test Line')
                        ->setPrice('100.00')
                        ->setQty(1)
                        ->updateTotal(),
                ],
                'terms' => 'Test Terms',
                'notes' => 'Test Notes',
                'discount' => new Discount(),
                'tax' => 0,
            ])
            ->_real();

        $uuid = Ulid::fromString(self::QUOTE_ID);
        $quote->setId($uuid)
            ->setUuid(Uuid::fromString(self::QUOTE_ID))
            ->setInvoice($invoice);
        $quote->setQuoteId('QUOT-2021-0001');

        $params = $action($request, $quote);

        $response = $twig->resolveTemplate('@SolidInvoiceQuote/Default/view.html.twig')->renderBlock('content', $params);

        $this->assertMatchesHtmlSnapshot($response);
    }

    public function testViewWithClientContacts(): void
    {
        $request = Request::createFromGlobals();
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $twig = self::getContainer()->get(Environment::class);

        $action = new View(
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

        /** @var Quote $quote */
        $quote = QuoteFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => QuoteStatus::Pending,
                'total' => '100.00',
                'baseTotal' => '100.00',
                'created' => new DateTimeImmutable('2021-09-01'),
                'lines' => [
                    (new Line())
                        ->setDescription('Test Line')
                        ->setPrice('100.00')
                        ->setQty(1)
                        ->updateTotal(),
                ],
                'terms' => 'Test Terms',
                'notes' => 'Test Notes',
                'discount' => new Discount(),
                'tax' => 0,
                'users' => [$contact],
            ])
            ->_real();

        $uuid = Ulid::fromString(self::QUOTE_ID);
        $quote->setId($uuid)
            ->setUuid(Uuid::fromString(self::QUOTE_ID));
        $quote->setQuoteId('QUOT-2021-0001');

        $params = $action($request, $quote);

        $response = $twig->resolveTemplate('@SolidInvoiceQuote/Default/view.html.twig')->renderBlock('content', $params);

        $this->assertMatchesHtmlSnapshot($response);
    }
}
