# Testing

Comprehensive testing guide for SolidInvoice. All tests must follow these conventions and best practices.

## Critical Testing Conventions

### Test Class Requirements

**REQUIRED for ALL test classes:**
1. **Final classes**: All test classes MUST be declared as `final`
2. **@covers annotation**: Required for unit/integration tests (not functional tests)
3. **One test class per production class**: Maintain 1:1 mapping

**Example:**
```php
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

namespace SolidInvoice\InvoiceBundle\Tests;

use PHPUnit\Framework\TestCase;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;

/**
 * @covers \SolidInvoice\InvoiceBundle\Manager\InvoiceManager
 */
final class InvoiceManagerTest extends TestCase
{
    // Test methods
}
```

## Directory Structure

```
src/BundleNameBundle/Tests/
├── Functional/           # Functional tests (full request/response cycle)
│   └── Api/             # API endpoint tests
├── Form/                # Form type tests
├── Repository/          # Repository tests
└── ...                  # Unit tests (top-level, NOT in Unit/ subdirectory)
```

## PHPUnit Configuration

Key settings from `phpunit.xml.dist`:

- **Random execution order**: Tests run in random order to catch hidden dependencies
- **Strict mode**: All warnings treated as failures
- **Database isolation**: DAMA/DoctrineTestBundle wraps each test in a transaction
- **E2E testing**: Symfony Panther for real browser testing
- **Test environment**: Uses `.env.test` configuration

## Mocking Guidelines

### Use PHPUnit Mocks (Not Mockery)

**DO:**
```php
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SolidInvoice\InvoiceBundle\Manager\InvoiceManager
 */
final class InvoiceManagerTest extends TestCase
{
    private NotifierInterface&MockObject $notifier;

    protected function setUp(): void
    {
        $this->notifier = $this->createMock(NotifierInterface::class);
    }

    public function testSendNotification(): void
    {
        $this->notifier
            ->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(Notification::class));

        $manager = new InvoiceManager($this->notifier);
        $manager->sendNotification(/* ... */);
    }
}
```

**DON'T:**
```php
// WRONG - Do not use Mockery
use Mockery as m;

$mock = m::mock(SomeClass::class);
```

### When to Use Mocks

**Only mock when:**
1. Making assertions on method calls (e.g., verifying a service method was called)
2. Testing error conditions that are hard to trigger with real objects
3. External services (HTTP clients, third-party APIs)

**Prefer concrete classes when:**
- Testing with real data is straightforward
- No assertions needed on method calls
- The dependency is a value object, DTO, or simple class

### What NOT to Mock

**NEVER mock:**
1. **Repository classes** - Use real repositories with test database
2. **Entities** - Create with Foundry factories
3. **Value objects** (Money, Email, etc.) - Use real instances
4. **DTOs and simple data structures** - Use real instances

**For repository tests:**
```php
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;

/**
 * @covers \SolidInvoice\InvoiceBundle\Repository\InvoiceRepository
 */
final class InvoiceRepositoryTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testFindOverdueInvoices(): void
    {
        $repository = static::getContainer()->get(InvoiceRepository::class);

        // Use Foundry factories to create test data
        InvoiceFactory::createOne(['dueDate' => new \DateTime('-5 days')]);
        InvoiceFactory::createOne(['dueDate' => new \DateTime('+5 days')]);

        $invoices = $repository->findOverdueInvoices();

        self::assertCount(1, $invoices);
    }
}
```

## Test Types

### 1. Unit Tests

Test individual classes in isolation. Fast execution, no database.

**Key points:**
- Extend `PHPUnit\Framework\TestCase`
- Mock only external dependencies
- Prefer concrete classes over mocks
- Test one class per test file

```php
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SolidInvoice\MoneyBundle\Formatter\MoneyFormatter
 */
final class MoneyFormatterTest extends TestCase
{
    private SystemConfig&MockObject $systemConfig;

    protected function setUp(): void
    {
        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->systemConfig
            ->method('getCurrency')
            ->willReturn(new Currency('USD'));
    }

    public function testFormatMoney(): void
    {
        $formatter = new MoneyFormatter('en_US', $this->systemConfig);
        $money = Money::USD(1250); // $12.50

        $result = $formatter->format($money);

        self::assertSame('$12.50', $result);
    }
}
```

### 2. Integration Tests (with Database)

Test classes that require database access.

**Key points:**
- Extend `Symfony\Bundle\FrameworkBundle\Test\KernelTestCase`
- Add `SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled` trait
- Get services from container: `static::getContainer()->get(ServiceClass::class)`
- Use Foundry factories for test data
- Each test runs in a transaction (automatic rollback)

```php
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;

/**
 * @covers \SolidInvoice\ClientBundle\Repository\ClientRepository
 */
final class ClientRepositoryTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testFindActiveClients(): void
    {
        $repository = static::getContainer()->get(ClientRepository::class);

        // Create test data
        ClientFactory::createOne(['archived' => false, 'name' => 'Active Client']);
        ClientFactory::createOne(['archived' => true, 'name' => 'Archived Client']);

        $clients = $repository->findActiveClients();

        self::assertCount(1, $clients);
        self::assertSame('Active Client', $clients[0]->getName());
    }
}
```

### 3. Functional Tests

Test full HTTP request/response cycle with database.

**Key points:**
- Extend `Symfony\Bundle\FrameworkBundle\Test\WebTestCase`
- Use `Zenstruck\Browser\Test\HasBrowser` trait
- Use `$this->browser()` for standard requests
- Use `$this->pantherBrowser()` for JavaScript/real browser testing
- Test from user's perspective

```php
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class InvoiceControllerTest extends WebTestCase
{
    use HasBrowser;

    public function testCreateInvoice(): void
    {
        $client = ClientFactory::createOne();

        $this->browser()
            ->visit('/invoices/create')
            ->fillField('invoice[client]', (string) $client->getId())
            ->fillField('invoice[total]', '1000')
            ->click('Save')
            ->assertSuccessful()
            ->assertOn('/invoices/{id}')
            ->assertSee('Invoice created successfully');
    }

    public function testDeleteInvoice(): void
    {
        $invoice = InvoiceFactory::createOne();

        $this->browser()
            ->visit("/invoices/{$invoice->getId()}")
            ->click('Delete')
            ->assertSee('Are you sure?') // confirmation modal
            ->click('Confirm')
            ->assertOn('/invoices')
            ->assertSee('Invoice deleted');
    }
}
```

**For JavaScript-heavy features:**
```php
public function testInvoiceFormWithJavaScript(): void
{
    $this->pantherBrowser() // Real Chrome/Firefox browser
        ->visit('/invoices/create')
        ->waitFor('.invoice-form') // Wait for JS to load
        ->fillField('client_search', 'Acme')
        ->waitFor('.autocomplete-results')
        ->click('.autocomplete-results li:first-child')
        ->fillField('amount', '500')
        ->click('Calculate Tax') // Triggers JS calculation
        ->waitForElementToContain('.total', '$550.00')
        ->click('Save')
        ->assertOn('/invoices/{id}');
}
```

### 4. API Tests

Test REST API endpoints with JSON responses.

**Key points:**
- Extend `ApiTestCase` base class (if available) or `WebTestCase`
- Test JSON-LD/HAL response formats
- Validate response structure
- Test authentication/authorization

```php
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class InvoiceApiTest extends WebTestCase
{
    use HasBrowser;

    public function testGetInvoicesCollection(): void
    {
        InvoiceFactory::createMany(5);

        $this->browser()
            ->get('/api/invoices', [
                'headers' => ['Accept' => 'application/ld+json'],
            ])
            ->assertStatus(200)
            ->assertJsonMatches('"hydra:totalItems"', 5)
            ->assertJsonMatches('[hydra:member][0]["@type"]', 'Invoice');
    }

    public function testCreateInvoiceViaApi(): void
    {
        $client = ClientFactory::createOne();

        $this->browser()
            ->post('/api/invoices', [
                'json' => [
                    'client' => '/api/clients/' . $client->getId(),
                    'total' => ['amount' => 10000, 'currency' => 'USD'],
                    'status' => 'draft',
                ],
                'headers' => [
                    'Accept' => 'application/ld+json',
                    'X-API-TOKEN' => $this->getApiToken(),
                ],
            ])
            ->assertStatus(201)
            ->assertJsonMatches('status', 'draft');
    }
}
```

### 5. Live Component Tests

Test Symfony UX Live Components with snapshot assertions.

**Key points:**
- Extend `SolidInvoice\CoreBundle\Test\LiveComponentTest`
- Use `Spatie\Snapshots\MatchesSnapshots` trait
- Test both initial render and re-renders
- Use snapshot assertions to catch UI regressions
- Use `createLiveComponent()` method with `name` and `data` parameters
- Chain `actingAs()` to set the authenticated user

```php
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use Spatie\Snapshots\MatchesSnapshots;

/**
 * @covers \SolidInvoice\InvoiceBundle\LiveComponent\InvoiceFormComponent
 */
final class InvoiceFormComponentTest extends LiveComponentTest
{
    use MatchesSnapshots;

    public function testInitialRender(): void
    {
        $invoice = InvoiceFactory::createOne(['status' => 'draft']);

        $component = $this->createLiveComponent(
            name: InvoiceFormComponent::class,
            data: [
                'invoice' => $invoice,
            ]
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($component->render());
    }

    public function testReRenderAfterStatusChange(): void
    {
        $component = $this->createLiveComponent(
            name: InvoiceFormComponent::class,
            data: [
                'invoice' => InvoiceFactory::createOne(['status' => 'draft']),
            ]
        )->actingAs($this->getUser());

        $component->set('status', 'sent');

        $this->assertMatchesHtmlSnapshot($component->render());
    }
}
```

## Test Data with Foundry

**ALWAYS use Foundry factories** to create test data. Never manually instantiate entities.

### Creating Test Data

```php
use function Zenstruck\Foundry\faker;

// Single entity
$invoice = InvoiceFactory::createOne([
    'total' => Money::USD(10000),
    'status' => 'draft',
]);

// Multiple entities
InvoiceFactory::createMany(5);

// With relationships
$client = ClientFactory::createOne();
$invoice = InvoiceFactory::createOne(['client' => $client]);

// Using faker for random data
$client = ClientFactory::createOne([
    'name' => faker()->company(),
    'email' => faker()->companyEmail(),
    'vat' => faker()->optional()->numerify('GB#########'),
]);

// Sequences for unique values
ClientFactory::createMany(10, function() {
    return ['email' => faker()->unique()->email()];
});
```

### Factory Best Practices

1. **Define sensible defaults in factories**
2. **Use faker() for realistic random data**
3. **Create relationships explicitly in tests**
4. **Use `createOne()` instead of `new()` for single entities**

## Running Tests

```bash
# All tests
bin/phpunit

# Specific bundle
bin/phpunit src/InvoiceBundle/Tests

# Specific test file
bin/phpunit src/InvoiceBundle/Tests/InvoiceManagerTest.php

# Specific test method
bin/phpunit --filter testCreateInvoice

# With coverage
bin/phpunit --coverage-html coverage

# Update snapshots (for Live Component tests)
bin/phpunit --update-snapshots

# Show deprecations
bin/phpunit --display-deprecations

# Exclude slow tests
bin/phpunit --exclude-group slow
```

## Testing Best Practices

### 1. Prefer Real Objects Over Mocks

**Good:**
```php
public function testInvoiceCalculation(): void
{
    $taxCalculator = new TaxCalculator(); // Real object
    $invoice = new Invoice();
    $invoice->setTaxCalculator($taxCalculator);

    $total = $invoice->calculateTotal();

    self::assertSame(Money::USD(11000), $total); // $100 + 10% tax
}
```

**Bad:**
```php
public function testInvoiceCalculation(): void
{
    $taxCalculator = $this->createMock(TaxCalculator::class); // Unnecessary mock
    $taxCalculator->method('calculate')->willReturn(Money::USD(1000));
    // ...
}
```

### 2. Use Descriptive Test Names

Test names should describe behavior, not implementation.

**Good:**
```php
public function testMarksInvoiceAsOverdueWhenPaymentDatePasses(): void
```

**Bad:**
```php
public function testCheckOverdue(): void
```

### 3. Follow Arrange-Act-Assert Pattern

```php
public function testClientArchiving(): void
{
    // Arrange
    $client = ClientFactory::createOne(['archived' => false]);
    $repository = static::getContainer()->get(ClientRepository::class);

    // Act
    $client->archive();
    $repository->save($client);

    // Assert
    $activeClients = $repository->findActiveClients();
    self::assertCount(0, $activeClients);
}
```

### 4. One Assertion Per Test (When Possible)

**Good:**
```php
public function testReturnsClientById(): void
{
    $client = ClientFactory::createOne();
    $repository = static::getContainer()->get(ClientRepository::class);

    $result = $repository->find($client->getId());

    self::assertSame($client->getId(), $result->getId());
}

public function testReturnsNullForNonexistentClient(): void
{
    $repository = static::getContainer()->get(ClientRepository::class);

    $result = $repository->find(Ulid::generate());

    self::assertNull($result);
}
```

### 5. Test Edge Cases

Don't just test the happy path:

```php
public function testRejectsFutureInvoiceDate(): void
{
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invoice date cannot be in the future');

    InvoiceFactory::createOne(['date' => new \DateTime('+1 week')]);
}

public function testHandlesEmptyResultSet(): void
{
    $repository = static::getContainer()->get(InvoiceRepository::class);

    $invoices = $repository->findOverdueInvoices();

    self::assertCount(0, $invoices);
}

public function testHandlesVeryLargeMoney(): void
{
    $formatter = new MoneyFormatter('en_US', $this->systemConfig);

    $result = $formatter->format(Money::USD(999999999999));

    self::assertSame('$9,999,999,999.99', $result);
}
```

### 6. Use Data Providers for Similar Tests

```php
/**
 * @dataProvider invoiceStatusProvider
 */
public function testInvoiceStatusTransitions(string $from, string $to, bool $allowed): void
{
    $invoice = InvoiceFactory::createOne(['status' => $from]);

    if (!$allowed) {
        $this->expectException(\LogicException::class);
    }

    $invoice->transitionTo($to);

    if ($allowed) {
        self::assertSame($to, $invoice->getStatus());
    }
}

/**
 * @return array<array{string, string, bool}>
 */
public static function invoiceStatusProvider(): array
{
    return [
        'draft to sent' => ['draft', 'sent', true],
        'sent to paid' => ['sent', 'paid', true],
        'paid to draft' => ['paid', 'draft', false],
        'cancelled to sent' => ['cancelled', 'sent', false],
    ];
}
```

### 7. Clean Up After Tests

PHPUnit and DAMA handle most cleanup, but for external resources:

```php
final class EmailServiceTest extends KernelTestCase
{
    private string $testFile;

    protected function setUp(): void
    {
        $this->testFile = sys_get_temp_dir() . '/test_email.txt';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }
}
```

## Common Patterns

### Testing Exceptions

```php
public function testThrowsExceptionForInvalidAmount(): void
{
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Amount must be positive');

    Money::USD(-1000);
}
```

### Testing Events

```php
public function testDispatchesInvoicePaidEvent(): void
{
    $eventDispatcher = static::getContainer()->get(EventDispatcherInterface::class);
    $listener = new TestEventListener();
    $eventDispatcher->addListener(InvoicePaidEvent::class, [$listener, 'onInvoicePaid']);

    $invoice = InvoiceFactory::createOne(['status' => 'pending']);
    $invoice->markAsPaid();

    self::assertTrue($listener->wasCalled());
}
```

### Testing with Time

```php
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class RecurringInvoiceTest extends KernelTestCase
{
    use ClockSensitiveTrait;

    public function testGeneratesInvoiceOnSchedule(): void
    {
        $clock = self::mockTime('2024-01-01 00:00:00');

        $scheduler = new RecurringInvoiceScheduler($clock);
        $template = InvoiceFactory::createOne(['recurring' => 'monthly']);

        // Move time forward
        $clock->sleep(31 * 24 * 60 * 60); // 31 days

        $generated = $scheduler->generateDueInvoices();

        self::assertCount(1, $generated);
    }
}
```

## Checklist for New Tests

Before submitting tests, verify:

- [ ] Test class is declared as `final`
- [ ] `@covers` annotation present (for unit tests)
- [ ] Uses PHPUnit mocks, not Mockery
- [ ] Prefers real objects over mocks where possible
- [ ] Uses Foundry factories for all entity creation
- [ ] Test names are descriptive
- [ ] Follows Arrange-Act-Assert pattern
- [ ] Tests edge cases and error conditions
- [ ] No hardcoded values (use factories, constants)
- [ ] All assertions have meaningful messages
- [ ] Test is isolated (no dependencies on other tests)
- [ ] Passes when run individually: `bin/phpunit --filter testMethodName`

## Summary

**Key principles:**
1. ✅ Test classes are `final` with `@covers` annotation
2. ✅ Use PHPUnit mocks, never Mockery
3. ✅ Prefer real objects over mocks
4. ✅ Never mock repositories, entities, or value objects
5. ✅ Always use Foundry factories for test data
6. ✅ Use zenstruck/browser for functional tests
7. ✅ Extend `LiveComponentTest` for Live Component tests with snapshots
8. ✅ Use `createLiveComponent()` method with `name` and `data` parameters
9. ✅ Test from the user's perspective
10. ✅ Write descriptive test names
11. ✅ Test both happy path and edge cases
