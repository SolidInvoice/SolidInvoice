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

use Carbon\CarbonImmutable;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Mode\ModeResolver;
use SolidInvoice\CoreBundle\Twig\Extension\ModeExtension;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;
use Twig\Environment;
use Zenstruck\Foundry\Test\Factories;

final class PdfDemoWatermarkTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private const string CLIENT_ID = '01JGXKV8QZ0000000000000001';

    private const string QUOTE_ID = '181aaf4a-0097-11ef-9b64-5a2cf21a5680';

    private function makeQuote(): Quote
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD', 'name' => 'Johnston PLC'])->_real();
        $client->setId(Ulid::fromString(self::CLIENT_ID));

        /** @var Quote $quote */
        $quote = QuoteFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => QuoteStatus::Pending,
                'total' => '100.00',
                'baseTotal' => '100.00',
                'created' => CarbonImmutable::parse('2021-09-01'),
                'lines' => [
                    new Line()
                        ->setDescription('Test Line')
                        ->setPrice('100.00')
                        ->setQty(1)
                        ->updateTotal(),
                ],
                'discount' => new Discount(),
                'tax' => 0,
            ])
            ->_real();
        $quote->setId(Ulid::fromString(self::QUOTE_ID))
            ->setQuoteId('QUOT-2021-0001');

        return $quote;
    }

    private function render(bool $demoEnabled): string
    {
        // ModeResolver is a widely-shared private service that is already initialized by the
        // time a test runs, so the container refuses to replace it directly. Overriding the
        // (lazily instantiated) Twig extension that consumes it achieves the same effect.
        self::getContainer()->set(
            ModeExtension::class,
            new ModeExtension($demoEnabled ? new ModeResolver('demo', 'demo@example.com', 'demo-password') : new ModeResolver()),
        );

        return self::getContainer()->get(Environment::class)
            ->render('@SolidInvoiceQuote/Pdf/quote.html.twig', ['quote' => $this->makeQuote()]);
    }

    public function testDemoWatermarkIsForcedWhenDemoEnabledEvenWithoutWatermarkSetting(): void
    {
        self::assertStringContainsString('content="DEMO"', $this->render(true));
    }

    public function testNoDemoWatermarkWhenDemoDisabled(): void
    {
        self::assertStringNotContainsString('content="DEMO"', $this->render(false));
    }
}
