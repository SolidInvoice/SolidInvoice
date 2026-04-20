## Codebase Patterns

- RoundingMode constants are used across many bundles (Money, Invoice, Quote, Payment, Core, API, Dashboard). Any brick/math API changes will have wide impact.
- `mcp__fff__multi_grep` with `*.php` constraint is the fastest way to find all usages of a constant across the codebase.
- Entity setters that accept `BigNumber|float|int|string` and pass to `BigNumber::of()` need `is_float()` guards — PHPStan won't catch these because the parameter type is a union, but runtime will fail with brick/math 0.17.
- Always run the full test suite after upgrading brick/math — functional tests (e.g. ViewTest rendering Twig templates) exercise code paths that PHPStan misses.

## US-001: Rename RoundingMode constants to PascalCase
- Replaced all `RoundingMode::HALF_EVEN` with `RoundingMode::HalfEven` (12 files, 13 occurrences)
- Replaced all `RoundingMode::HALF_UP` with `RoundingMode::HalfUp` (2 files, 2 occurrences)
- Files changed (14 total):
  - `src/CoreBundle/Billing/TotalCalculator.php` (3 occurrences)
  - `src/ApiBundle/Serializer/Normalizer/BigIntegerNormalizer.php`
  - `src/CoreBundle/Doctrine/Type/BigIntegerType.php`
  - `src/CoreBundle/Form/Transformer/DiscountTransformer.php`
  - `src/DashboardBundle/Widgets/RevenueChartWidget.php`
  - `src/InvoiceBundle/Search/InvoiceResultFormatter.php`
  - `src/InvoiceBundle/Search/RecurringInvoiceResultFormatter.php`
  - `src/MoneyBundle/Form/DataTransformer/ViewTransformer.php`
  - `src/MoneyBundle/Twig/Extension/MoneyFormatterExtension.php`
  - `src/PaymentBundle/Action/Prepare.php`
  - `src/PaymentBundle/Search/PaymentResultFormatter.php`
  - `src/QuoteBundle/Search/QuoteResultFormatter.php`
  - `src/InvoiceBundle/DummyData/InvoiceDummyDataLoader.php`
  - `src/QuoteBundle/DummyData/QuoteDummyDataLoader.php`
- **Learnings for future iterations:**
  - Purely mechanical rename; no logic changes needed
  - ECS, PHPStan, and pre-commit hooks all pass cleanly with the new PascalCase constants
  - The Edit tool requires reading a file before editing it; batch reads first, then batch edits

## US-002: Cast float arguments to string in core billing logic
- Cast float expressions to `(string)` before passing to `dividedBy()`, `multipliedBy()`, and `BigDecimal::of()` in TotalCalculator
- Cast `$percentage / 100` to string in `Calculator::calculatePercentage()`
- Guarded `BigNumber::of()` calls in Discount entity with `is_float($value) ? (string) $value : $value`
- Files changed (3 total):
  - `src/CoreBundle/Billing/TotalCalculator.php` (3 casts: lines 76, 80, 84)
  - `src/MoneyBundle/Calculator.php` (1 cast: line 54)
  - `src/CoreBundle/Entity/Discount.php` (3 guards: lines 71, 104, 110)
- All 14 tests in TotalCalculatorTest and CalculatorTest pass
- **Learnings for future iterations:**
  - The `is_float() ? (string) : $value` guard pattern preserves type flexibility for callers passing BigNumber|int|string while only casting floats
  - `$rowTax->getRate()` returns `float`, so arithmetic on it (e.g. `/ 100 + 1`) also produces float — cast the full expression, not just `getRate()`
  - Pre-commit hooks (linting, ECS, YAML lint) all pass cleanly

## US-003: Cast float arguments to string in serializers and transformers
- Added `is_float() ? (string) : $value` guard before all `BigNumber::of()` calls in serializers, transformers, and the Credit action
- Refactored Credit action to extract json_decode result into a variable for cleaner guarding
- Used inline guard `is_float($value) ? (string) $value : $value` inside `BigNumber::of()` for MoneyFormatterExtension where a separate line would break the closure flow
- Files changed (6 total):
  - `src/ApiBundle/Serializer/Normalizer/BigIntegerNormalizer.php` (1 guard for `$data`, covers both code paths)
  - `src/ApiBundle/Serializer/Normalizer/CreditNormalizer.php` (1 guard for `$data`)
  - `src/CoreBundle/Form/Transformer/DiscountTransformer.php` (2 guards: `transform()` and `reverseTransform()`)
  - `src/MoneyBundle/Form/DataTransformer/ViewTransformer.php` (2 guards: `transform()` and `reverseTransform()`)
  - `src/MoneyBundle/Twig/Extension/MoneyFormatterExtension.php` (1 inline guard)
  - `src/ClientBundle/Action/Ajax/Credit.php` (1 guard, extracted `$creditInput` variable)
- All 26 tests in BigIntegerNormalizerTest and ViewTransformerTest pass
- **Learnings for future iterations:**
  - When the input variable is used in a single `BigNumber::of()` call, guarding once before it is cleanest
  - For inline closures/lambdas, the guard can be placed inside the `BigNumber::of()` call itself
  - Complex nested expressions (like json_decode chains) should be extracted to a variable first, then guarded

## US-004: Cast float arguments to string in payment and dummy data loaders
- Guarded `$invoice->getDiscount()->getValue()` with `is_float() ? (string) : $value` before passing to `multipliedBy()` in CapturePaymentAction
- Removed `(float)` cast from `random_int(1, 10)` in both InvoiceDummyDataLoader and QuoteDummyDataLoader (int is natively accepted by brick/math)
- Cast `$tax->getRate()` to `(string)` before passing to `multipliedBy()` in both dummy data loaders
- Files changed (3 total):
  - `src/PaymentBundle/PaymentAction/PaypalExpress/CapturePaymentAction.php` (extracted `$discountValue`, guarded with `is_float()`)
  - `src/InvoiceBundle/DummyData/InvoiceDummyDataLoader.php` (removed `(float)` cast on `$qty`, cast `$tax->getRate()` to string)
  - `src/QuoteBundle/DummyData/QuoteDummyDataLoader.php` (same changes as InvoiceDummyDataLoader)
- **Learnings for future iterations:**
  - `Tax::getRate()` always returns `float`, so a direct `(string)` cast is safe — no need for the `is_float()` guard pattern
  - `random_int()` returns `int`, which brick/math accepts natively — casting to `(float)` was unnecessary and introduced the very problem we're fixing
  - The `is_float() ? (string) : $value` guard is only needed when the return type is a union (e.g. `float|int|string|BigNumber`); for known-float returns, `(string)` suffices

## US-005: Fix float literals in test files
- Replaced float literals with string literals in `BigDecimal::of()` and `BigNumber::of()` calls across 3 test files
- For fractional values (e.g. `1.1`, `10000.1`), changed to string literals (`'1.1'`, `'10000.1'`) to preserve decimal precision
- For whole-number floats (e.g. `30000.00`, `200.00`, `2000.0`), changed to integer literals (`30000`, `200`, `2000`) since no decimal precision is needed
- Files changed (3 total):
  - `src/ApiBundle/Tests/Serializer/Normalizer/BigIntegerNormalizerTest.php` (3 changes: lines 39, 40, 66)
  - `src/CoreBundle/Tests/Billing/TotalCalculatorTest.php` (2 changes: lines 170-171)
  - `src/MoneyBundle/Tests/CalculatorTest.php` (1 change: line 39)
- All 18 tests pass, ECS and PHPStan clean
- **Learnings for future iterations:**
  - Fractional float literals must become strings (not ints) to preserve the decimal component
  - Whole-number float literals (e.g. `30000.00`) can safely become plain integers since `BigDecimal::of(30000)` and `BigDecimal::of('30000')` are equivalent
  - Verified with `mcp__fff__grep` regex search that zero float literals remain in test files for brick/math factory methods

## US-006: Update composer.json and verify full test suite
- Updated `"brick/math": "^0.14.0"` to `"^0.17.0"` in composer.json
- Ran `composer update brick/math --with-all-dependencies` — upgraded from 0.14.8 to 0.17.0
- Fixed 3 additional PHPStan errors discovered after upgrade:
  - `InvoiceBundle/Entity/Line.php:247` — cast `$this->qty` (float|null) to string before `multipliedBy()`
  - `QuoteBundle/Entity/Line.php:245` — same fix
  - `MoneyBundle/Calculator.php:39` — cast `calculatePercentage()` return (float) to string before `BigDecimal::of()`; removed `float` from `calculatePercentage()` `$amount` parameter union type
- Fixed 13 test failures (all in ViewTest) caused by `BaseInvoice::hasDiscount()` passing float to `BigNumber::of()`
- Added `is_float()` guards to 5 additional entity setters that accept `float` in their union type:
  - `InvoiceBundle/Entity/BaseInvoice.php` (hasDiscount)
  - `InvoiceBundle/Entity/Line.php` (setPrice, setTotal)
  - `QuoteBundle/Entity/Line.php` (setPrice, setTotal)
  - `ClientBundle/Entity/Credit.php` (setValue)
- Files changed (6 total + composer.json/composer.lock):
  - `composer.json` (version constraint)
  - `composer.lock` (updated)
  - `src/ClientBundle/Entity/Credit.php`
  - `src/InvoiceBundle/Entity/BaseInvoice.php`
  - `src/InvoiceBundle/Entity/Line.php`
  - `src/MoneyBundle/Calculator.php`
  - `src/QuoteBundle/Entity/Line.php`
- All 1335 tests pass, PHPStan clean (2 pre-existing migration errors only), ECS clean
- **Learnings for future iterations:**
  - PHPStan doesn't flag float-to-BigNumber issues when the parameter type is a union (e.g. `BigNumber|float|int|string`) — runtime tests are essential
  - Functional tests that render Twig templates exercise entity method calls that unit tests and static analysis miss
  - `composer update` may need `--ignore-platform-req=ext-redis` in development environments without Redis extension
  - The `is_float() ? (string) : $value` guard pattern in entity setters preserves backward compatibility for callers passing float while satisfying brick/math 0.17's stricter type requirements
