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

namespace SolidInvoice\ProductBundle\Tests\Functional;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Money\Currency;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Cloner\InvoiceCloner;
use SolidInvoice\InvoiceBundle\Entity\Line as InvoiceLine;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceLine;
use SolidInvoice\InvoiceBundle\Form\Type\ItemType;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\ProductBundle\Test\Factory\ProductFactory;
use SolidInvoice\QuoteBundle\Cloner\QuoteCloner;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use function array_key_exists;
use function random_bytes;
use function sprintf;
use function str_contains;
use function strtolower;

#[Group('functional')]
final class LineProductLinkTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCreatingInvoiceFromQuoteKeepsProduct(): void
    {
        $product = ProductFactory::createOne(['company' => $this->company]);
        $quote = QuoteFactory::createOne(['client' => ClientFactory::createOne(['company' => $this->company]), 'company' => $this->company]);
        $quote->addLine($this->quoteLine($product));

        $invoice = self::getContainer()->get(InvoiceManager::class)->createFromQuote($quote);

        self::assertSame($product, $invoice->getLines()->first()->getProduct());
    }

    public function testRecurringInvoiceGeneratesInvoiceWithProduct(): void
    {
        $product = ProductFactory::createOne(['company' => $this->company]);
        $recurring = RecurringInvoiceFactory::createOne(['client' => ClientFactory::createOne(['company' => $this->company]), 'company' => $this->company]);
        $line = new RecurringInvoiceLine();
        $line->setName('Hosting')->setPrice(10)->setQty(1)->setTotal(10)->setProduct($product);
        $recurring->addLine($line);

        $invoice = self::getContainer()->get(InvoiceManager::class)->createFromRecurring($recurring);

        self::assertSame($product, $invoice->getLines()->first()->getProduct());
    }

    public function testClonedInvoiceKeepsProduct(): void
    {
        $product = ProductFactory::createOne(['company' => $this->company]);
        // Numbered, as every saved invoice is: PostgreSQL's TO_NUMBER rejects the empty default.
        $invoice = InvoiceFactory::createOne(['client' => ClientFactory::createOne(['company' => $this->company]), 'company' => $this->company, 'invoiceId' => '1']);
        $invoice->addLine($this->invoiceLine($product));

        $clone = self::getContainer()->get(InvoiceCloner::class)->clone($invoice);

        self::assertSame($product, $clone->getLines()->first()->getProduct());
    }

    public function testClonedQuoteKeepsProduct(): void
    {
        $product = ProductFactory::createOne(['company' => $this->company]);
        $quote = QuoteFactory::createOne(['client' => ClientFactory::createOne(['company' => $this->company]), 'company' => $this->company, 'quoteId' => '1']);
        $quote->addLine($this->quoteLine($product));

        $clone = self::getContainer()->get(QuoteCloner::class)->clone($quote);

        self::assertSame($product, $clone->getLines()->first()->getProduct());
    }

    public function testHiddenProductFieldResolvesOwnProductAndDropsForeignOrMalformedIds(): void
    {
        $mine = ProductFactory::createOne(['company' => $this->company]);
        $theirs = ProductFactory::createOne(['company' => CompanyFactory::createOne(['name' => 'Other Inc'])]);
        // Creating a company can move the active one, so pin it back.
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        // A request starts with an empty identity map; find() only skips the company filter on a hit.
        $this->em->clear();

        $submit = fn (string $productId): ?Product => self::getContainer()->get(FormFactoryInterface::class)
            ->create(ItemType::class, new InvoiceLine(), ['currency' => new Currency('USD'), 'csrf_protection' => false])
            ->submit(['name' => 'Hosting', 'price' => '10', 'qty' => '1', 'product' => $productId])
            ->getData()
            ->getProduct();

        self::assertSame($mine->getId()->toBase58(), $submit((string) $mine->getId())?->getId()->toBase58());
        self::assertNotInstanceOf(Product::class, $submit((string) $theirs->getId()));
        self::assertNotInstanceOf(Product::class, $submit('not-a-ulid'));
        self::assertNotInstanceOf(Product::class, $submit(''));
    }

    /**
     * The link is cleared by the foreign key, not by any PHP code, so it has to be tested
     * against the schema the mapping generates. The suite's own connection cannot do that:
     * SQLite ignores `PRAGMA foreign_keys` inside the transaction dama/doctrine-test-bundle
     * keeps open, and MySQL (production) always enforces it. A throwaway in-memory database,
     * built from the same mapping, enforces it from the start.
     */
    #[DataProvider('lineTables')]
    public function testDeletingProductKeepsLineValuesAndNullsLink(string $table): void
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

        // Foreign keys off while seeding, so the rows need no parents beyond the product.
        $connection->executeStatement('PRAGMA foreign_keys = OFF');

        // Rendered for this connection's platform: the suite's own may be MySQL or PostgreSQL.
        $schema = new SchemaTool($this->em)->getSchemaFromMetadata($this->em->getMetadataFactory()->getAllMetadata());

        foreach ($schema->toSql($connection->getDatabasePlatform()) as $sql) {
            $connection->executeStatement($sql);
        }

        $productId = random_bytes(16);
        $lineId = random_bytes(16);

        $this->insert($connection, Product::TABLE_NAME, ['id' => $productId, 'name' => 'Website design', 'price' => '4500.000000']);
        $this->insert($connection, $table, ['id' => $lineId, 'name' => 'Website design', 'price_amount' => '4500', 'product_id' => $productId]);

        $connection->executeStatement('PRAGMA foreign_keys = ON');
        $connection->executeStatement('DELETE FROM ' . Product::TABLE_NAME . ' WHERE id = ?', [$productId]);

        self::assertSame(
            ['name' => 'Website design', 'price_amount' => 4500, 'product_id' => null],
            $connection->fetchAssociative(sprintf('SELECT name, price_amount, product_id FROM %s WHERE id = ?', $table), [$lineId]),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function lineTables(): iterable
    {
        yield 'invoice lines' => [InvoiceLine::TABLE_NAME];
        yield 'quote lines' => [QuoteLine::TABLE_NAME];
    }

    /**
     * @param array<string, mixed> $values
     */
    private function insert(Connection $connection, string $table, array $values): void
    {
        // Every NOT NULL column without a default gets a placeholder, so a test only has to
        // name the columns it cares about.
        foreach ($connection->fetchAllAssociative(sprintf('PRAGMA table_info(%s)', $table)) as $column) {
            if ($column['notnull'] && $column['dflt_value'] === null && ! array_key_exists($column['name'], $values)) {
                $values[$column['name']] = str_contains(strtolower((string) $column['type']), 'blob') ? random_bytes(16) : '2024-01-01 00:00:00';
            }
        }

        $connection->insert($table, $values);
    }

    private function invoiceLine(Product $product): InvoiceLine
    {
        return new InvoiceLine()->setName('Website design')->setPrice(4500)->setQty(1)->setTotal(4500)->setProduct($product);
    }

    private function quoteLine(Product $product): QuoteLine
    {
        return new QuoteLine()->setName('Website design')->setPrice(4500)->setQty(1)->setTotal(4500)->setProduct($product);
    }
}
