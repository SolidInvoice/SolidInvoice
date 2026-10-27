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

namespace SolidInvoice\ApiBundle\Tests\Functional\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ApiBundle\Versioning\ApiVersion;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[Group('functional')]
final class MoneyFormatTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Invoice::class;
    }

    /**
     * @return iterable<string, array{string, string, string, string, int}>
     */
    public static function currencies(): iterable
    {
        yield 'two decimals' => ['USD', '10.5', '10.50', '21.00', 1050];
        yield 'no decimals' => ['JPY', '1250', '1250', '2500', 1250];
        yield 'three decimals' => ['BHD', '12.5', '12.500', '25.000', 12500];
    }

    #[DataProvider('currencies')]
    public function testMoneyIsADecimalStringAtTheCurrencyPrecision(string $currency, string $price, string $expectedPrice, string $expectedTotal, int $storedPrice): void
    {
        $response = $this->postInvoice($currency, ['price' => $price, 'qty' => '2']);

        self::assertResponseStatusCodeSame(201);
        self::assertFalse($response->getHeaders(false)['deprecation'][0] ?? false);

        $result = $response->toArray(false);

        self::assertSame($currency, $result['currencyCode']);
        self::assertSame($expectedPrice, $result['lines'][0]['price']);
        self::assertSame('2', $result['lines'][0]['qty']);
        self::assertSame($expectedTotal, $result['lines'][0]['total']);
        self::assertSame($expectedTotal, $result['total']);
        self::assertSame($expectedTotal, $result['balance']);

        $invoice = self::getContainer()->get(InvoiceRepository::class)->find(Ulid::fromString($result['id']));
        self::assertInstanceOf(Invoice::class, $invoice);
        self::assertSame($storedPrice, $invoice->getLines()->first()->getPrice()->toBigInteger()->toInt());
    }

    public function testMoneyDiscountUsesTheCurrencyPrecision(): void
    {
        $response = $this->postInvoice('BHD', ['price' => '10', 'qty' => 1], ['type' => 'money', 'value' => '1.25']);

        self::assertResponseStatusCodeSame(201);
        $result = $response->toArray(false);

        self::assertSame(['type' => 'money', 'value' => '1.250'], $result['discount']);
        self::assertSame('8.750', $result['total']);
    }

    public function testALinePostedUnderAnInvoiceUsesThatInvoiceCurrency(): void
    {
        $result = $this->postInvoice('JPY', ['price' => '100', 'qty' => 1])->toArray(false);

        $response = self::$client->request('POST', '/api/invoices/' . $result['id'] . '/lines', [
            'headers' => ['accept' => 'application/ld+json', ApiVersion::HEADER => ApiVersion::V2026_10->value],
            'json' => ['name' => 'Extra', 'price' => '1250', 'qty' => '1'],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame('1250', $response->toArray(false)['price']);
    }

    public function testMorePrecisionThanTheCurrencyAllowsIsRejected(): void
    {
        $this->postInvoice('JPY', ['price' => '12.5', 'qty' => 1]);

        self::assertResponseStatusCodeSame(400);
    }

    public function testClientCreditUsesTheClientCurrency(): void
    {
        $response = self::$client->request('POST', '/api/clients', [
            'headers' => ['accept' => 'application/ld+json', ApiVersion::HEADER => ApiVersion::V2026_10->value],
            'json' => ['name' => 'Yen Client', 'currencyCode' => 'JPY', 'credit' => '500'],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame('500', $response->toArray(false)['credit']);
    }

    public function testRequestsWithoutAVersionKeepTheOriginalFormatAndAreDeprecated(): void
    {
        $response = $this->postInvoice('USD', ['price' => 10.5, 'qty' => 2], version: null);

        self::assertResponseStatusCodeSame(201);
        self::assertSame(21, $response->toArray(false)['total']);
        self::assertResponseHeaderSame('deprecation', ApiVersion::LEGACY_DEPRECATION);
        self::assertResponseHeaderSame('sunset', ApiVersion::LEGACY_SUNSET);
    }

    public function testAnUnknownVersionIsRejected(): void
    {
        $this->postInvoice('USD', ['price' => '1', 'qty' => 1], version: '1999-01');

        self::assertResponseStatusCodeSame(400);
    }

    /**
     * @param array<string, mixed> $line
     * @param array<string, mixed> $discount
     */
    private function postInvoice(string $currency, array $line, array $discount = ['type' => 'percentage', 'value' => 0], ?string $version = ApiVersion::V2026_10->value): ResponseInterface
    {
        $client = ClientFactory::createOne(['currencyCode' => $currency]);
        $contact = ContactFactory::createOne(['client' => $client]);

        $headers = ['accept' => 'application/ld+json'];

        if ($version !== null) {
            $headers[ApiVersion::HEADER] = $version;
        }

        return self::$client->request('POST', '/api/invoices', [
            'headers' => $headers,
            'json' => [
                'client' => $this->getIriFromResource($client),
                'users' => [$this->getIriFromResource($contact)],
                'discount' => $discount,
                'lines' => [['name' => 'Item', ...$line]],
            ],
        ]);
    }
}
