# Testing

## Structure

```
src/BundleNameBundle/Tests/
├── Functional/
│   └── Api/       # API tests
├── Form/          # Form type tests
├── Repository/    # Repository tests
└── ...            # Unit tests (top-level)
```

## PHPUnit Config

- Random execution order
- Strict mode (warnings = failures)
- DAMA/DoctrineTestBundle for DB transaction isolation
- Symfony Panther for E2E browser tests
- Environment: `.env.test`

## Commands

```bash
bin/phpunit                              # All tests
bin/phpunit src/InvoiceBundle/Tests      # Specific bundle
bin/phpunit path/to/TestFile.php         # Specific file
bin/phpunit --filter testMethodName      # Specific test
bin/phpunit --coverage-html coverage     # With coverage
```

## Test Types

### Unit Tests

- Test classes in isolation
- Use Mockery for mocking
- Fast, no database

```php
use Mockery as m;

class InvoiceManagerTest extends TestCase
{
    public function testCreateInvoice(): void
    {
        $repository = m::mock(InvoiceRepository::class);
        // ...
    }
}
```

### Functional Tests

- Full request/response cycle
- Uses database
- Located in `Tests/Functional/`

### API Tests

- Test REST endpoints
- Extend `ApiTestCase`
- JSON-LD/HAL validation

## Fixtures

Use **Foundry** for factory-based fixtures.
