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

namespace SolidInvoice\ProductBundle\Tests\Twig\Components;

use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\InvoiceBundle\DTO\InvoiceFormDTO;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line as InvoiceLine;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceLine;
use SolidInvoice\InvoiceBundle\Form\Type\InvoiceType;
use SolidInvoice\InvoiceBundle\Form\Type\RecurringInvoiceType;
use SolidInvoice\InvoiceBundle\Twig\Components\CreateInvoice;
use SolidInvoice\InvoiceBundle\Twig\Components\CreateRecurringInvoice;
use SolidInvoice\ProductBundle\Repository\ProductRepository;
use SolidInvoice\ProductBundle\Test\Factory\ProductFactory;
use SolidInvoice\ProductBundle\Twig\Components\AddsProductLines;
use SolidInvoice\QuoteBundle\DTO\QuoteFormDTO;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use SolidInvoice\QuoteBundle\Form\Type\QuoteType;
use SolidInvoice\QuoteBundle\Twig\Components\CreateQuote;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Repository\TaxRepository;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

final class AddProductLineTest extends LiveComponentTest
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function documents(): iterable
    {
        yield 'invoice' => ['invoice'];
        yield 'quote' => ['quote'];
        yield 'recurring invoice' => ['recurring_invoice'];
    }

    #[DataProvider('documents')]
    public function testAddsFilledLine(string $document): void
    {
        $product = ProductFactory::createOne([
            'name' => 'Website design',
            'description' => '5-page site',
            'price' => BigDecimal::of('4500.00'),
            'quantity' => BigDecimal::of('2'),
            'company' => $this->company,
        ]);

        $component = $this->component($document);
        $component->set($document . '.addProduct', (string) $product->getId());

        $before = $component->component()->formValues['lines'];

        $component->call('addProductLine');

        $values = $component->component()->formValues;
        $lines = $values['lines'];

        self::assertCount(count($before) + 1, $lines);
        self::assertSame(array_values($before), array_slice($lines, 0, count($before)));

        $added = $lines[count($lines) - 1];
        self::assertSame('Website design', $added['name']);
        self::assertSame('5-page site', $added['description']);
        // The re-render after the action runs the values through the form, which pads the scale.
        self::assertTrue(BigDecimal::of($added['price'])->isEqualTo('4500'));
        self::assertTrue(BigDecimal::of($added['qty'])->isEqualTo('2'));
        self::assertSame((string) $product->getId(), $added['product']);
        self::assertSame('', $values['addProduct']);
    }

    #[DataProvider('documents')]
    public function testAddsTheProductsTaxInTheShapeOfLineTaxType(string $document): void
    {
        $tax = new Tax()->setName('VAT')->setRate(20)->setType(Tax::TYPE_EXCLUSIVE);
        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($tax);
        $em->flush();

        $product = ProductFactory::createOne(['tax' => $tax, 'company' => $this->company]);

        $component = $this->component($document);
        $component->set($document . '.addProduct', (string) $product->getId());
        $component->call('addProductLine');

        $added = array_slice($component->component()->formValues['lines'], -1)[0];

        self::assertSame((string) $tax->getId(), $added['taxes'][0]['tax']);
        // The re-rendered line shows the tax choice label.
        self::assertStringContainsString('VAT', $component->render()->toString());
    }

    #[DataProvider('documents')]
    public function testProductEditAfterAddDoesNotChangeLine(string $document): void
    {
        $product = ProductFactory::createOne(['name' => 'Hosting', 'price' => BigDecimal::of('10'), 'company' => $this->company]);

        $component = $this->component($document);
        $component->set($document . '.addProduct', (string) $product->getId());
        $component->call('addProductLine');

        $product->setName('Renamed')->setPrice(BigDecimal::of('99'));
        self::getContainer()->get('doctrine')->getManager()->flush();

        $component->set($document . '.addProduct', '');
        $added = array_slice($component->component()->formValues['lines'], -1)[0];

        self::assertSame('Hosting', $added['name']);
        self::assertTrue(BigDecimal::of($added['price'])->isEqualTo('10'));
    }

    #[DataProvider('documents')]
    public function testUnknownOrForeignProductIsIgnored(string $document): void
    {
        // Built first: creating another company moves the active one, which redirects the editor.
        $component = $this->component($document);
        $count = count($this->linesOf($component));

        $theirs = ProductFactory::createOne(['company' => CompanyFactory::createOne(['name' => 'Other Inc'])]);
        $foreignId = (string) $theirs->getId();
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        foreach ([(string) new Ulid(), $foreignId, 'not-a-ulid'] as $id) {
            $component->set($document . '.addProduct', $id);
            $component->call('addProductLine');
            self::assertCount($count, $component->component()->formValues['lines']);
        }
    }

    public function testZeroDecimalCurrency(): void
    {
        $product = ProductFactory::createOne(['price' => BigDecimal::of('4500'), 'company' => $this->company]);

        foreach (['JPY' => Money::JPY(4500), 'BHD' => Money::BHD(4500000)] as $code => $expected) {
            $client = ClientFactory::createOne(['currencyCode' => $code, 'company' => $this->company]);
            ContactFactory::createOne(['client' => $client]);

            $dto = new InvoiceFormDTO();
            $dto->invoiceDate = CarbonImmutable::parse('2021-01-01');
            $dto->client = $client;
            $dto->users->add($client->getContacts()->first());

            $component = $this->createLiveComponent(
                name: CreateInvoice::class,
                data: ['dto' => $dto],
                client: $this->client,
            )->actingAs($this->getUser());

            $component->set('invoice.addProduct', (string) $product->getId());
            $component->call('addProductLine');
            $component->call('saveDraft');

            $invoice = self::getContainer()->get('doctrine')->getRepository(Invoice::class)->findOneBy(['client' => $client]);
            self::assertInstanceOf(Invoice::class, $invoice);
            self::assertTrue($invoice->getLines()->first()->getPrice()->isEqualTo($expected->getAmount()), $code);
            self::assertSame($product->getId()->toString(), $invoice->getLines()->first()->getProduct()?->getId()->toString());
        }
    }

    public function testGateOffAddsNothing(): void
    {
        // The gate is read while services are built, so it has to be swapped on a fresh kernel.
        self::ensureKernelShutdown();
        self::bootKernel();

        $gate = $this->createStub(FeatureGate::class);
        $gate->method('isEnabled')->willReturn(false);
        self::getContainer()->set(FeatureGate::class, $gate);

        $factory = self::getContainer()->get(FormFactoryInterface::class);
        foreach ([[InvoiceType::class, new InvoiceFormDTO()], [QuoteType::class, new QuoteFormDTO()], [RecurringInvoiceType::class, new RecurringInvoice()]] as [$type, $data]) {
            self::assertFalse($factory->create($type, $data)->has('addProduct'), $type);
        }

        $product = ProductFactory::createOne(['company' => $this->company]);
        $subject = $this->subject($gate);
        $subject->formValues = ['lines' => [], 'addProduct' => (string) $product->getId()];

        $subject->addProductLine();

        self::assertSame([], $subject->formValues['lines']);
        self::assertSame((string) $product->getId(), $subject->formValues['addProduct']);
    }

    /**
     * A bare user of the trait, for the cases a live component cannot render.
     */
    private function subject(FeatureGate $gate): object
    {
        return new class($gate, self::getContainer()->get(ProductRepository::class), self::getContainer()->get(TaxRepository::class)) {
            use AddsProductLines;

            /** @var array<string, mixed> */
            public array $formValues = [];

            public function __construct(
                private readonly FeatureGate $featureGate,
                private readonly ProductRepository $productRepository,
                private readonly TaxRepository $taxRepository,
            ) {
            }
        };
    }

    private function component(string $document): TestLiveComponent
    {
        $component = match ($document) {
            'invoice' => $this->createLiveComponent(CreateInvoice::class, ['dto' => $this->invoiceDto()]),
            'quote' => $this->createLiveComponent(CreateQuote::class, ['dto' => $this->quoteDto()]),
            default => $this->createLiveComponent(CreateRecurringInvoice::class, ['invoice' => $this->recurring()]),
        };

        return $component->actingAs($this->getUser());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function linesOf(TestLiveComponent $component): array
    {
        return array_values($component->component()->formValues['lines'] ?? []);
    }

    private function invoiceDto(): InvoiceFormDTO
    {
        $dto = new InvoiceFormDTO();
        $dto->invoiceDate = CarbonImmutable::parse('2021-01-01');
        $dto->lines->add(new InvoiceLine()->setName('Existing')->setPrice(100)->setQty(1));

        return $dto;
    }

    private function quoteDto(): QuoteFormDTO
    {
        $dto = new QuoteFormDTO();
        $dto->lines->add(new QuoteLine()->setName('Existing')->setPrice(100)->setQty(1));

        return $dto;
    }

    private function recurring(): RecurringInvoice
    {
        return new RecurringInvoice()->addLine(new RecurringInvoiceLine()->setName('Existing')->setPrice(100)->setQty(1));
    }
}
