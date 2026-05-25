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

namespace SolidInvoice\McpBundle\Tests\Functional;

use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Mcp\InvoiceWriteTools;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Mcp\QuoteWriteTools;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Test\Factory\TaxFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

#[Group('functional')]
final class MultiTaxLineTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testInvoiceLineWithIndiaGstSplit(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'INR'])->_real();

        $cgst = TaxFactory::createOne([
            'name' => 'CGST',
            'rate' => 9.0,
            'type' => Tax::TYPE_EXCLUSIVE,
            'category' => TaxCategory::Standard,
            'company' => $this->company,
        ])->_real();

        $sgst = TaxFactory::createOne([
            'name' => 'SGST',
            'rate' => 9.0,
            'type' => Tax::TYPE_EXCLUSIVE,
            'category' => TaxCategory::Standard,
            'company' => $this->company,
        ])->_real();

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $result = $tool->createInvoice(
            $client->getId()->toRfc4122(),
            [
                [
                    'description' => 'Consulting',
                    'price' => 100000,
                    'qty' => 1,
                    'taxes' => [
                        ['tax_id' => $cgst->getId()->toRfc4122(), 'sequence' => 0, 'compound' => false],
                        ['tax_id' => $sgst->getId()->toRfc4122(), 'sequence' => 1, 'compound' => false],
                    ],
                ],
            ],
        );

        self::assertArrayHasKey('lines', $result);
        self::assertCount(1, $result['lines']);
        self::assertCount(2, $result['lines'][0]['taxes']);

        $taxNames = array_column($result['lines'][0]['taxes'], 'name');
        self::assertSame(['CGST', 'SGST'], $taxNames);
        self::assertSame([0, 1], array_column($result['lines'][0]['taxes'], 'sequence'));

        $invoice = self::getContainer()->get('doctrine')->getRepository(Invoice::class)->find(Ulid::fromString($result['id']));
        self::assertInstanceOf(Invoice::class, $invoice);
        self::assertCount(2, $invoice->getLines()->first()->getTaxes());
    }

    public function testQuoteLineWithQuebecCompound(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'CAD'])->_real();

        $gst = TaxFactory::createOne([
            'name' => 'GST',
            'rate' => 5.0,
            'type' => Tax::TYPE_EXCLUSIVE,
            'category' => TaxCategory::Standard,
            'company' => $this->company,
        ])->_real();

        $qst = TaxFactory::createOne([
            'name' => 'QST',
            'rate' => 9.975,
            'type' => Tax::TYPE_EXCLUSIVE,
            'category' => TaxCategory::Standard,
            'compound' => true,
            'company' => $this->company,
        ])->_real();

        $tool = self::getContainer()->get(QuoteWriteTools::class);
        self::assertInstanceOf(QuoteWriteTools::class, $tool);

        $result = $tool->createQuote(
            $client->getId()->toRfc4122(),
            [
                [
                    'description' => 'Service',
                    'price' => 10000,
                    'qty' => 1,
                    'taxes' => [
                        ['tax_id' => $gst->getId()->toRfc4122(), 'sequence' => 0, 'compound' => false],
                        ['tax_id' => $qst->getId()->toRfc4122(), 'sequence' => 1, 'compound' => true],
                    ],
                ],
            ],
        );

        self::assertArrayHasKey('lines', $result);
        self::assertCount(1, $result['lines']);
        $taxes = $result['lines'][0]['taxes'];
        self::assertCount(2, $taxes);
        self::assertSame('GST', $taxes[0]['name']);
        self::assertSame('QST', $taxes[1]['name']);
        self::assertFalse($taxes[0]['compound']);
        self::assertTrue($taxes[1]['compound']);

        $quote = self::getContainer()->get('doctrine')->getRepository(Quote::class)->find(Ulid::fromString($result['id']));
        self::assertInstanceOf(Quote::class, $quote);
        $line = $quote->getLines()->first();
        self::assertCount(2, $line->getTaxes());

        $persistedTaxes = $line->getTaxes()->toArray();
        usort($persistedTaxes, static fn ($a, $b): int => $a->getSequence() <=> $b->getSequence());
        self::assertSame('GST', $persistedTaxes[0]->getNameSnapshot());
        self::assertSame('QST', $persistedTaxes[1]->getNameSnapshot());
        self::assertTrue($persistedTaxes[1]->isCompound());
    }

    public function testLegacySingleTaxIdStillWorks(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company])->_real();
        $vat = TaxFactory::createOne([
            'name' => 'VAT',
            'rate' => 20.0,
            'type' => Tax::TYPE_EXCLUSIVE,
            'company' => $this->company,
        ])->_real();

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $result = $tool->createInvoice(
            $client->getId()->toRfc4122(),
            [
                ['description' => 'Item', 'price' => 1000, 'qty' => 1, 'tax_id' => $vat->getId()->toRfc4122()],
            ],
        );

        self::assertCount(1, $result['lines'][0]['taxes']);
        self::assertSame('VAT', $result['lines'][0]['taxes'][0]['name']);
    }

    public function testRejectsMissingTaxId(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company])->_real();

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('tax_id is required');

        $tool->createInvoice(
            $client->getId()->toRfc4122(),
            [
                ['description' => 'Item', 'price' => 1000, 'qty' => 1, 'taxes' => [['sequence' => 0]]],
            ],
        );
    }

    /**
     * @param list<string> $scopes
     */
    private function activateScopes(array $scopes): void
    {
        $container = self::getContainer();

        $stack = $container->get(RequestStack::class);
        self::assertInstanceOf(RequestStack::class, $stack);

        while ($stack->getMainRequest() instanceof Request) {
            $stack->pop();
        }

        $request = new Request();
        $request->attributes->set(McpOAuthAuthenticator::ATTR_SCOPES, $scopes);

        $stack->push($request);

        $selector = $container->get(CompanySelector::class);
        self::assertInstanceOf(CompanySelector::class, $selector);
        $selector->switchCompany($this->company->getId());
    }
}
