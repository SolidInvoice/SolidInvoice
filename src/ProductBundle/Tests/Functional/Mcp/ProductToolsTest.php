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

namespace SolidInvoice\ProductBundle\Tests\Functional\Mcp;

use Doctrine\ORM\EntityManagerInterface;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\ProductBundle\Mcp\ProductReadTools;
use SolidInvoice\ProductBundle\Mcp\ProductWriteTools;
use SolidInvoice\ProductBundle\Repository\ProductRepository;
use SolidInvoice\TaxBundle\Repository\TaxRepository;
use SolidInvoice\TaxBundle\Test\Factory\TaxFactory;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Group('functional')]
final class ProductToolsTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testCreateListGetUpdateDeleteRoundTrip(): void
    {
        $this->setActiveScopes([McpScope::Read->value, McpScope::Write->value]);

        $write = self::getContainer()->get(ProductWriteTools::class);
        $read = self::getContainer()->get(ProductReadTools::class);
        self::assertInstanceOf(ProductWriteTools::class, $write);
        self::assertInstanceOf(ProductReadTools::class, $read);

        $tax = TaxFactory::createOne(['company' => $this->company, 'name' => 'VAT']);

        $created = $write->createProduct('Widget', '12.5', 'A widget', '2', $tax->getId()->toRfc4122());

        self::assertSame('Widget', $created['name']);
        self::assertSame('A widget', $created['description']);
        self::assertSame('12.5', $created['price']);
        self::assertSame('2', $created['quantity']);
        self::assertSame('VAT', $created['tax']['name']);
        self::assertSame($tax->getId()->toRfc4122(), $created['tax']['id']);

        $id = $created['id'];

        self::assertSame(1, $read->listProducts()['count']);
        self::assertSame(1, $read->listProducts('widg')['count']);
        self::assertSame(0, $read->listProducts('nothing-like-this')['count']);
        self::assertSame($id, $read->getProduct($id)['id']);

        $updated = $write->updateProduct($id, name: 'Gadget', price: '99.99', tax_id: null);
        self::assertSame('Gadget', $updated['name']);
        self::assertSame('99.99', $updated['price']);
        self::assertSame('2', $updated['quantity'], 'Untouched fields are kept.');
        self::assertSame('A widget', $updated['description']);

        self::assertSame(['deleted' => true, 'id' => $id], $write->deleteProduct($id));
        self::assertSame(0, $read->listProducts()['count']);
    }

    public function testCreateWithForeignTaxFails(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $foreignTax = $this->foreignTax();

        $write = self::getContainer()->get(ProductWriteTools::class);
        self::assertInstanceOf(ProductWriteTools::class, $write);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('not found');

        $write->createProduct('Widget', '1', tax_id: $foreignTax);
    }

    public function testGetForeignProductIsNotFound(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $read = self::getContainer()->get(ProductReadTools::class);
        self::assertInstanceOf(ProductReadTools::class, $read);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('not found');

        $read->getProduct((string) new Ulid());
    }

    public function testInvalidInputsBecomeToolCallExceptions(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $write = self::getContainer()->get(ProductWriteTools::class);
        self::assertInstanceOf(ProductWriteTools::class, $write);

        foreach ([
            ['Widget', 'abc'],
            ['Widget', '1/3'],
            ['Widget', '1', null, '1/3'],
            ['Widget', '-1'],
            ['Widget', '1.1234567'],
            ['Widget', '12345678901234'],
            ['Widget', '1e999999'],
            ['', '1'],
            [str_repeat('x', 300), '1'],
            ['Widget', '1', null, '0'],
        ] as $args) {
            try {
                $write->createProduct(...$args);
                self::fail('Expected ToolCallException for ' . json_encode($args));
            } catch (ToolCallException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testFailedUpdateLeavesTheManagedProductUntouched(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $write = self::getContainer()->get(ProductWriteTools::class);
        $id = $write->createProduct('Widget', '5')['id'];

        try {
            $write->updateProduct($id, name: 'Changed', price: '-1');
            self::fail('Expected ToolCallException.');
        } catch (ToolCallException) {
        }

        // A parse failure must not leave the already-set name on the managed entity either.
        try {
            $write->updateProduct($id, name: 'Changed', price: '1/3');
            self::fail('Expected ToolCallException.');
        } catch (ToolCallException) {
        }

        $product = self::getContainer()->get(ProductRepository::class)->find(Ulid::fromString($id));
        self::assertSame('Widget', $product->getName());
        self::assertSame('5', (string) $product->getPrice());
    }

    public function testGateOffRejectsEveryTool(): void
    {
        $this->setActiveScopes([McpScope::Read->value, McpScope::Write->value]);

        $container = self::getContainer();
        $gate = $this->createStub(FeatureGate::class);
        $gate->method('isEnabled')->willReturn(false);

        $read = new ProductReadTools($container->get(ProductRepository::class), $container->get(EntityNormalizer::class), $container->get(McpScopeGuard::class), $gate);
        $write = new ProductWriteTools(
            $container->get(ProductRepository::class),
            $container->get(TaxRepository::class),
            $container->get(EntityManagerInterface::class),
            $container->get(EntityNormalizer::class),
            $container->get(McpScopeGuard::class),
            $container->get(ValidatorInterface::class),
            $gate,
        );

        $id = (string) new Ulid();

        foreach ([
            static fn () => $read->listProducts(),
            static fn () => $read->getProduct($id),
            static fn () => $write->createProduct('Widget', '1'),
            static fn () => $write->updateProduct($id),
            static fn () => $write->deleteProduct($id),
        ] as $call) {
            try {
                $call();
                self::fail('Expected the plan gate to reject the call.');
            } catch (ToolCallException $e) {
                self::assertSame('Products are not available on your plan.', $e->getMessage());
            }
        }
    }

    private function foreignTax(): string
    {
        $tax = TaxFactory::createOne(['company' => CompanyFactory::createOne(['name' => 'Foreign Co'])]);
        $id = $tax->getId()->toRfc4122();

        // find() hits the identity map before the SQL filter, so drop it; creating the other
        // company also leaves the company filter unbound, so bind ours again.
        self::getContainer()->get(EntityManagerInterface::class)->clear();
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        return $id;
    }

    /**
     * @param list<string> $scopes
     */
    private function setActiveScopes(array $scopes): void
    {
        $stack = self::getContainer()->get(RequestStack::class);

        while ($stack->getMainRequest() instanceof Request) {
            $stack->pop();
        }

        $request = new Request();
        $request->attributes->set(McpOAuthAuthenticator::ATTR_SCOPES, $scopes);

        $stack->push($request);

        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());
    }
}
