# ADR 0001 — `EInvoiceBundle` architecture and interfaces

**Status:** Accepted · **Date:** 2026-09-22 · **Owner:** Staff Engineer
**Issue:** SOL-80 / [SolidInvoice#2653](https://github.com/SolidInvoice/SolidInvoice/issues/2653)
**Supersedes:** the *Target architecture* page in the Outline wiki, which is explicitly marked
indicative. Where the two differ, this ADR wins.

**Amendments:** two, both binding — §2.2, §7.4, §11 and §13 are corrected. See [§16](#16-amendments).

Grounded in SolidInvoice `3.1.x` at `68ad56366`. Every file path and line reference below was read
in that tree. Claims I could not verify are labelled **unverified** and are listed in §14.

## Inputs this ADR is built on

Three Phase 0 spikes are decided and are inherited here without re-litigation:

| Spike | Decision inherited |
|---|---|
| SOL-24 / #2650 | Factur-X PDF/A-3 is generated in-process by the existing `mpdf`. No new PDF dependency. The archival path is a **separate method** on `CoreBundle\Pdf\Generator`, not a variant of `generate()`. |
| SOL-67 / #2651 | `john-wink/en16931-php` pinned to exactly `0.3.0`, behind our own interface, with the KoSIT oracle in **our** CI. No JRE on the default path. Five PM conditions apply (§6). |
| SOL-68 / #2652 | Compose per-syntax writers behind our own `SyntaxWriterInterface`. `horstoeko/invoicesuite` is not adopted as a facade. Money becomes a scalar in exactly one place. |

---

## 1. The one principle — the Adapter Boundary Rule

The PM's ruling on SOL-67 observed that SOL-67 and SOL-68 arrived at the same architectural
instinct independently, and asked that it be stated once as a principle rather than rediscovered
per component. This is that statement.

> **Every external e-invoicing capability enters the application through a SolidInvoice-owned
> interface, taking and returning SolidInvoice-owned value objects. No vendor type appears in the
> signature of any interface declared in this ADR, in any Action class, in any API Platform
> resource, in any Doctrine entity, or in any Twig template.**

Three consequences, all binding:

1. **Every adopted e-invoicing package is pinned to an exact version**, never a caret range. These
   are compliance-path dependencies; a patch release that changes a verdict is a correctness
   regression, not an improvement.
2. **Swapping an engine is an adapter change.** One class per vendor. If replacing
   `john-wink/en16931-php` requires touching anything outside `Validation/Engine/`, the boundary has
   been compromised and the reversibility argument the PM ruling rests on has lapsed.
3. **Conformance is measured by us, not asserted by the vendor.** The KoSIT parity harness and the
   127 negative mutations from SOL-67 live in this repository and run in our CI.

This rule is also what makes the `ChannelInterface` requirement in §7 enforceable rather than
aspirational — it is the same rule, applied to transmission.

---

## 2. Bundle ownership and layout

### 2.1 A new bundle, always loaded

`src/EInvoiceBundle/`, namespace `SolidInvoice\EInvoiceBundle`, bundle class
`SolidInvoiceEInvoiceBundle`, DI extension `SolidInvoiceEInvoiceExtension` (mirroring
`src/TaxBundle/DependencyInjection/SolidInvoiceTaxExtension.php`).

Registered in `config/bundles.php` as `SolidInvoiceEInvoiceBundle::class => ['all' => true]`.

**It is not gated on `AppMode`.** Format generation, packaging and validation are free capabilities
of the self-hosted product (§9). A bundle that only loads in `saas` mode would make that impossible.

### 2.2 Layout

```text
src/EInvoiceBundle/
├── Action/              # HTTP entry points, invokable
├── Channel/             # ChannelInterface, registry, TransmissionResult
│   └── Peppol/          #   bring-your-own-credentials access-point adapters
├── Command/             # einvoice:credentials:rotate-key, einvoice:poll
├── Credentials/         # CredentialVaultInterface + defuse-backed implementation
├── DependencyInjection/
├── Entity/              # EInvoiceDocument, EInvoiceCredential, EInvoiceConfiguration
├── Enum/                # SyntaxFormat, EInvoiceStatus, EInvoiceTransition, ValidationStage,
│                        # ViolationSeverity, TransmissionState, InvoiceTypeCode, UnitCode,
│                        # PaymentMeansCode, EasScheme, VatCategoryCode, …
├── Exception/
├── Jurisdiction/        # JurisdictionRegistry reading Resources/config/jurisdictions.php
├── Listener/
├── Manager/
├── Mapper/              # Invoice entity → semantic model
├── Message/             # Messenger messages + handlers
├── Model/               # EN 16931 semantic DTOs — readonly, BT/BG-named
├── Packaging/           # FacturXPackager, XmlPackager
├── Profile/             # ProfileInterface, registry, RuleSet
├── Repository/
├── Resources/config/{routing,services,jurisdictions.php,severity_overrides.php}
├── Resources/translations/
├── Resources/views/
├── Syntax/              # SyntaxWriterInterface, SyntaxReaderInterface, registry
├── Validation/          # ValidatorInterface, CompositeValidator, report model
│   └── Engine/          #   the pinned third-party adapter lives here and nowhere else
├── Twig/
└── Tests/               # Functional/ for functional; unit tests at the top level
```

Two departures from the wiki's sketch, both deliberate:

- **No `Workflow/` directory.** The workflow is declared in `config/packages/workflow.php`
  alongside `invoice`, `recurring_invoice` and `quote`, because that is where every other state
  machine in this application lives. Transition names are an enum in `Enum/` (§8.3).
- **`Packaging/` does not own mpdf.** See §5.

### 2.3 Cross-bundle coupling — called out explicitly

| Bundle | What `EInvoiceBundle` depends on | Direction |
|---|---|---|
| `CoreBundle` | `Entity\Company`, `Traits\Entity\{CompanyAware,TimeStampable}`, `Pdf\Generator::generateArchival()` (new, SOL-24) | EInvoice → Core |
| `InvoiceBundle` | `Entity\Invoice`, `Entity\Line`, and later `CreditNote` (SOL-69 / #2657) | EInvoice → Invoice |
| `ClientBundle` | `Entity\{Client,Address,Contact}` | EInvoice → Client |
| `TaxBundle` | `Entity\{InvoiceTax,LineTax}`, `Enum\TaxCategory`, the snapshotted tax rows | EInvoice → Tax |
| `MoneyBundle` | `Currency\CurrencyScale` | EInvoice → Money |
| `SettingsBundle` | `Config\ProviderInterface` for per-company settings | EInvoice → Settings |
| `SaasBundle` | **Inverted.** `SaasBundle` implements `EInvoiceBundle`'s `ChannelInterface`; `EInvoiceBundle` never references `SaasBundle`. | Saas → EInvoice |
| `ApiBundle` / `McpBundle` | read-only exposure of `EInvoiceDocument` | those → EInvoice |
| `NotificationBundle` | acceptance / rejection notifications | EInvoice → Notification |

**`EInvoiceBundle` must never reference `SolidInvoice\SaasBundle`.** That inversion is the whole of
the feature-gating design and §9 depends on it.

---

## 3. The pipeline

```text
Invoice ─► Mapper ─► EInvoice ─► Profile ─► SyntaxWriter ─► Packager ─► Validator ─► Channel
(entity)             (EN 16931   (CIUS      (UBL | CII)     (XML |      (schema →    (Peppol AP,
                      DTOs,       rules)                     PDF/A-3)    syntax →     KSeF, SdI,
                      BigDecimal)                                        business)    Cloud relay)
                                                                             │             │
                                                                             ▼             ▼
                                                                    ValidationReport   EInvoiceDocument
                                                                                       (own workflow,
                                                                                        own audit trail)
```

Each stage is an interface resolved from a tagged registry. Adding a country is, in the best case,
a row in `jurisdictions.php` plus a `Profile` — with no change to the layers below.

**Validation is mandatory on the send path and not silently bypassable** (#2673). `ChannelInterface
::send()` is never called on a document whose last `ValidationReport` is not `Valid`. That check
lives in `Manager\TransmissionManager`, not in the individual adapters, so a new adapter cannot
forget it.

---

## 4. The semantic model

### 4.1 Shape

Immutable `final readonly` DTOs under `Model/`, named after the EN 16931 business groups: `EInvoice`
(root), `Seller`, `Buyer`, `Payee`, `PostalAddress`, `InvoiceLine`, `ItemInformation`, `PriceDetails`,
`VatBreakdown`, `DocumentTotals`, `PaymentInstructions`, `Allowance`, `Charge`, `DocumentReference`,
`DeliveryInformation`.

Every property docblock names its BT/BG code. Model against **EN 16931-1:2026** (#2656).

No `Model/` class may reference a Doctrine entity, a vendor type, or `Money\Money`.

### 4.2 Money — the single conversion point

This is the hardest constraint in the ADR and the one most likely to be violated by accident.

- Entities store money as **minor units in an integer column**; the factor is not always 100
  (`src/MoneyBundle/Currency/CurrencyScale.php`).
- `Model/` holds `Brick\Math\BigDecimal`, in **major units**, always. Never `float`, never
  `Money\Money`, never a scaled integer.
- **`Mapper/` never rounds.** Converting a stored integer of minor units to a major-unit
  `BigDecimal` is exact and lossless. If a value needs rounding at map time, that is a bug upstream
  in `TaxBundle`'s calculation, not something the mapper may paper over. Rounding is a configured
  setting (`TaxBundle\Enum\RoundingStrategy`) and must not be re-decided here.
- `horstoeko/zugferd`, `num-num/ubl-invoice` and every alternative evaluated in SOL-68 **take
  `float`** for monetary values. That conversion happens at the last possible call, inside a
  `Syntax/*Writer.php`, on a `BigDecimal` already scaled to the currency's subunit count via
  `CurrencyScale::subunitFor()`. EN 16931's `BR-DEC-*` rules require exact decimal counts, so the
  scaling is not cosmetic.
- **Enforcement:** a unit test in `Tests/Model/NoFloatConversionTest.php` scans `src/EInvoiceBundle/`
  and fails if `toFloat(`, `(float)` or `floatval(` appears outside `Syntax/`. Without a mechanical
  check this rule will rot; there are ~40 monetary business terms and one careless line is enough.

### 4.3 Missing terms map to null

Where the entity cannot yet supply a business term, the mapper sets `null`. It never fabricates a
default. The validator then reports what is missing, with the BT code, so the user sees *"BT-27
Seller name is required"* rather than a silently wrong invoice. The Epic A issues (SOL-84 … SOL-95)
fill those terms in over time; the mapper's shape does not change as they land.

---

## 5. Packaging — the seam with `CoreBundle`

Inherited from SOL-24 and restated here because it is the decision most likely to be undone by
someone who has not read that spike.

- **`CoreBundle` owns the archival-PDF mechanics.** `src/CoreBundle/Pdf/Generator.php` gains
  `generateArchival(string $html, ArchivalOptions $options): string`, plus `ArchivalOptions`,
  `AssociatedFile` and the `AssociatedFileRelationship` backed enum, all in `src/CoreBundle/Pdf/`.
  `generate()` stays byte-identical for its five existing call sites.
- **`EInvoiceBundle` owns the Factur-X semantics.** `Packaging/FacturXPackager` builds the
  `fx:DocumentType` / `fx:DocumentFileName` / `fx:Version` / `fx:ConformanceLevel` XMP block, names
  the attachment `factur-x.xml`, sets `AssociatedFileRelationship::Alternative`, and calls
  `Generator::generateArchival()`. **It must not know that mpdf exists.**
- `Packaging/XmlPackager` is the trivial case: it returns the `SyntaxDocument` payload with the
  right media type and filename for download or attachment.

Three verified incompatibilities make the archival path a separate method rather than a flag
(`vendor/mpdf/mpdf/src/Mpdf.php:9531`, `:10558`, `:1896`): PDF/A forbids encryption, forbids
watermarks — and `invoice/watermark` defaults to `'1'` at
`src/InvoiceBundle/Config/ConfigProvider.php:33` — and escalates the `opacity: 0.9` at
`assets/scss/pdf.scss:222` into a throw. Run the archival path with `PDFAauto = false` so drift
fails loudly in CI rather than silently emitting a PDF that claims PDF/A-3B and is not.

**Blocking prerequisite:** no veraPDF run has happened. #2650's conformance criterion is not met by
the spike. veraPDF validation (#2672 / SOL-113) must land *before* Factur-X packaging (#2670 /
SOL-107) — see §12.

---

## 6. Validation

### 6.1 Interfaces

```php
namespace SolidInvoice\EInvoiceBundle\Validation;

#[AutoconfigureTag(ValidatorInterface::DI_TAG)]
interface ValidatorInterface
{
    public const string DI_TAG = 'solidinvoice.einvoice.validator';

    public function supports(ProfileInterface $profile, ValidationStage $stage): bool;

    public function validate(SyntaxDocument $document, ProfileInterface $profile): ValidationReport;
}
```

```php
enum ValidationStage: string
{
    case Schema   = 'schema';    // XSD — the document is malformed
    case Syntax   = 'syntax';    // UBL-CR / CII-SR binding rules
    case Business = 'business';  // BR-*, BR-CO-*, BR-DE-*, PEPPOL-EN16931-*
    case Archival = 'archival';  // PDF/A-3B conformance
}

enum ViolationSeverity: string
{
    case Error       = 'error';
    case Warning     = 'warning';
    case Information = 'information';
}

enum ValidationOutcome: string
{
    case Valid        = 'valid';
    case Invalid      = 'invalid';
    case NotValidated = 'not_validated';
}
```

```php
final readonly class ValidationViolation
{
    public function __construct(
        public string $ruleId,              // 'BR-06', 'PEPPOL-EN16931-R020', 'cvc-complex-type.2.4.a'
        public ViolationSeverity $severity,
        public ValidationStage $stage,
        public ?string $businessTerm,       // 'BT-27', 'BG-23' — null when the rule names none
        public ?string $xpath,
        public string $message,
    ) {
    }
}

final readonly class ValidationReport
{
    /** @param list<ValidationViolation> $violations */
    public function __construct(
        public ValidationOutcome $outcome,
        public array $violations = [],
    ) {
    }

    public function isValid(): bool;
    /** @return list<ValidationViolation> */
    public function errors(): array;
    /** @return list<ValidationViolation> */
    public function warnings(): array;
    public static function merge(self ...$reports): self;
}
```

`$message` is a plain `string`, not a `TranslatableInterface`. The EN 16931 rule texts are published
English normative text; translating 274 of them is not Phase 1 work. The **framing** around a
violation — the stage name, the "this invoice will be rejected" explanation, the field link — is
translated and belongs to #2674 / SOL-114. Recorded here so that issue does not stall on the
question.

### 6.2 `ValidationOutcome::NotValidated` is not a third flavour of valid

SOL-67 §6 established the degraded position: if the engine is unavailable, the answer is
`NotValidated` and the UI must never render it as green. Schema-invalid, rule-invalid and
unvalidated are three different states and the UI must not conflate them. Making this an enum rather
than a nullable bool is what stops that happening by accident.

### 6.3 Composite and ordering

`CompositeValidator implements ValidatorInterface` resolves every tagged validator supporting the
profile and runs them in `ValidationStage` order, **short-circuiting after `Schema` produces an
error**. Business-rule output over a malformed document is noise that buries the real problem.

### 6.4 The engine adapter, and the five PM conditions

`Validation/Engine/En16931PhpValidator` is the **only** class in the repository permitted to
reference a `JohnWink\` type. Composer constraint: `"john-wink/en16931-php": "0.3.0"` — exact, never
`^0.3`.

The PM's five conditions land as follows, so that "done" is unambiguous:

| Condition | Where it is discharged |
|---|---|
| 1. Wrap + exact pin | The Adapter Boundary Rule (§1) and the composer constraint above. A PHPStan rule or unit test asserting no `JohnWink\` symbol outside `Validation/Engine/` is part of #2673 / SOL-108. |
| 2. Our CI oracle is the acceptance gate | The KoSIT harness and the 127 negative mutations move into this repo under `tests/Conformance/` and run in CI over **documents SolidInvoice generates**. Compare on `<rep:reject>` / `<rep:accept>` and the full report — **not** the package's `code="…"` regex, which cannot see KoSIT's schema-stage and scenario-selection rejections (SOL-67 §1b). Lands with #2669 / SOL-106. |
| 3. BR-06 false negative closed before users see validation | `Validation/Rule/MandatoryTermGuard` — a `ValidatorInterface` implementation at `ValidationStage::Business` with higher priority than the engine adapter, asserting BT-27 presence independently of the library's verdict. Ships with #2673 / SOL-108 regardless of upstream. Contributing the ~5-line reader fix upstream is additionally requested but is not the gate. |
| 4. Stricter verdict on severity mismatch | `Resources/config/severity_overrides.php` — a `ruleId => ViolationSeverity` map applied by the engine adapter after it maps the library's violations. `BR-CL-23` is the one measured case; the systematic KoSIT severity diff is a **required** task of #2669 / SOL-106, not optional. The map is data so that adding a case is a one-line change with a test. |
| 5. No certified/guaranteed-compliance claims | Binding on all settings copy, validation messages, docs and release notes. Enforced at review. Named in the docs issue #2700 / SOL-126. |

**No JRE on the default path**, affirmed and binding beyond SOL-67. A JRE is acceptable only in our
CI as the oracle, and as an opt-in sidecar for Docker/Kubernetes and Cloud installs. The fallback
engine — KoSIT in daemon mode behind a second `ValidatorInterface` implementation — is designed for
but not built. SOL-67 §6 lists its trigger conditions; they are inherited unchanged.

`ext-bcmath` is a documentation item for the install docs (#2700), not a blocker: the shipped static
binary already carries it (`frankenphp/build-static.sh:80`) and Composer's platform check fails
loudly for bring-your-own-PHP installs.

---

## 7. Syntax, profiles and channels

### 7.1 Syntax

```php
namespace SolidInvoice\EInvoiceBundle\Syntax;

#[AutoconfigureTag(SyntaxWriterInterface::DI_TAG)]
interface SyntaxWriterInterface
{
    public const string DI_TAG = 'solidinvoice.einvoice.syntax_writer';

    public function supports(SyntaxFormat $format, ProfileInterface $profile): bool;

    /** @throws SyntaxWriterException */
    public function write(EInvoice $invoice, ProfileInterface $profile): SyntaxDocument;
}

#[AutoconfigureTag(SyntaxReaderInterface::DI_TAG)]
interface SyntaxReaderInterface
{
    public const string DI_TAG = 'solidinvoice.einvoice.syntax_reader';

    public function supports(SyntaxFormat $format): bool;

    /** @throws SyntaxReaderException */
    public function read(string $payload): EInvoice;
}

final readonly class SyntaxDocument
{
    public function __construct(
        public string $payload,
        public SyntaxFormat $format,
        public string $mediaType,   // 'application/xml'
        public string $filename,    // 'factur-x.xml', 'invoice-ubl.xml'
    ) {
    }
}

enum SyntaxFormat: string
{
    case Ubl = 'ubl';
    case Cii = 'cii';
}
```

`write()` returns `SyntaxDocument` rather than `string` because the packager needs the media type and
the packager *and* the Factur-X XMP block both need the filename. Returning a bare string forces
every caller to re-derive them, and the one that gets it wrong produces a PDF whose XMP disagrees
with its attachment name.

`SyntaxFormat` is a closed backed enum. Adding a syntax means adding a case **and** a writer — which
is the point: a syntax with no writer must not be representable.

`Syntax/Ubl/UblSyntaxWriter` and `Syntax/Cii/CiiSyntaxWriter`. The CII writer uses
`horstoeko/zugferd` (SOL-68 proved byte-identical EN 16931 CII output). **The UBL writer library is
not selected by this ADR** — `num-num/ubl-invoice`'s own README states it is not feature-complete
for UBL/Peppol, and SOL-68 explicitly declined to endorse it. That choice belongs to #2666 / SOL-103
(§14).

### 7.2 Profiles

```php
namespace SolidInvoice\EInvoiceBundle\Profile;

#[AutoconfigureTag(ProfileInterface::DI_TAG)]
interface ProfileInterface
{
    public const string DI_TAG = 'solidinvoice.einvoice.profile';

    public function getIdentifier(): string;         // 'peppol-bis-billing-3.0'
    public function getCustomizationId(): string;    // BT-24
    public function getProfileId(): ?string;         // BT-23, null where the CIUS defines none
    public function getSyntax(): SyntaxFormat;
    /** @return list<string> BT codes mandatory beyond EN 16931 core, e.g. ['BT-10'] */
    public function getMandatoryTerms(): array;
    /** @return list<RuleSet> */
    public function getRuleSets(): array;
    public function getLabel(): TranslatableInterface;
    public function isValidVatInvoice(): bool;       // false for Factur-X MINIMUM / BASIC WL
}

final readonly class RuleSet
{
    public function __construct(
        public string $identifier,   // 'en16931', 'xrechnung', 'peppol-bis-billing-3.0'
        public string $version,      // pinned — '3.0.2'
        public ValidationStage $stage,
    ) {
    }
}
```

`isValidVatInvoice()` exists because #2671 requires MINIMUM and BASIC WL to be visibly marked as not
being valid VAT invoices in Germany. That is a property of the profile, not UI copy, so the UI cannot
forget it.

`RuleSet` names an identifier and a **pinned version**; it does **not** carry a Schematron artifact
path. SOL-67 measured that `XSLTProcessor::importStylesheet()` returns `false` on the official
compiled Schematron (XSLT 2.0, libxslt 1.1.35 supports 1.1). Whoever can evaluate a rule set says so
via `ValidatorInterface::supports()`. #2675's wording ("register the Schematron artifacts") is
superseded on this point — see §14 open item 1.

### 7.3 Channels — provider-agnostic *and* syntax-agnostic

```php
namespace SolidInvoice\EInvoiceBundle\Channel;

#[AutoconfigureTag(ChannelInterface::DI_TAG)]
interface ChannelInterface
{
    public const string DI_TAG = 'solidinvoice.einvoice.channel';

    public function getIdentifier(): string;
    public function getLabel(): TranslatableInterface;
    public function supports(EInvoiceDocument $document): bool;
    public function send(EInvoiceDocument $document): TransmissionResult;
    public function poll(EInvoiceDocument $document): TransmissionResult;
}

final readonly class TransmissionResult
{
    /** @param list<TransmissionMessage> $messages */
    public function __construct(
        public TransmissionState $state,
        public ?string $externalId = null,
        public ?string $receipt = null,        // the platform response, stored verbatim
        public array $messages = [],
        public ?DateTimeImmutable $occurredAt = null,
        public ?DateTimeImmutable $pollAfter = null,
    ) {
    }
}

enum TransmissionState: string
{
    case InProgress = 'in_progress';
    case Accepted   = 'accepted';
    case Rejected   = 'rejected';
    case Failed     = 'failed';
}
```

**The ADR states, as #2653 requires, that `ChannelInterface` must not expose provider-specific
concepts — and extends that to syntax-specific ones.** Both halves matter, and the second is the one
with a real-world precedent behind it.

Invoice Ninja's shared gateway contract is `MutatorInterface`, declaring `setPeppol()` / `getPeppol()`
typed against `\InvoiceNinja\EInvoice\Models\Peppol\Invoice`. They *did* add a second provider
(`app/Services/EDocument/Gateway/` now holds both `Storecove/` and `Qvalia/`), so the interface
generalises across Peppol access-point providers. What it cannot accommodate is a **clearance**
channel — Poland's KSeF, Italy's SdI, Romania's e-Factura — because those do not exchange a Peppol
document. Their abstraction is bound to a syntax, and that binding is invisible until the first
non-Peppol jurisdiction arrives, at which point the interface itself has to change and every adapter
with it.

So: no `Peppol`, `Ubl` or `Cii` type may appear in a `ChannelInterface` signature. The channel
receives an `EInvoiceDocument`, which already carries the generated payload and its syntax; it does
not receive a typed document model. Storecove's `legalEntityId` and KSeF's session token live in
those adapters' own configuration and are read from the vault (§10) by the adapter itself — they are
never parameters.

`pollAfter` lets a channel express its own rate limit without the caller knowing anything about the
provider. That is the test to apply to any future addition to this interface: *can a caller use it
without knowing which provider is behind it?*

**Inbound is not `receive()` on this interface.** Not every channel supports webhooks, and a method
that half the adapters throw from is worse than no method. Webhook-capable channels additionally
implement `Channel\WebhookCapableInterface`, built on `symfony/webhook` (already a dependency).
Polling covers the rest. This is a change from the wiki's `send / poll / receive` sketch.

**A `Channel\NullChannel` is part of the same issue as the interface** (#2677 / SOL-92), so every
downstream issue can be tested without network access.

### 7.4 Registries

One shape, three times:

```php
final readonly class ChannelRegistry
{
    /** @param iterable<ChannelInterface> $channels */
    public function __construct(private iterable $channels)
    {
    }

    /** @throws UnknownChannelException */
    public function get(string $identifier): ChannelInterface;

    public function has(string $identifier): bool;

    /** @return list<ChannelInterface> */
    public function all(): array;
}
```

Wired with `tagged_iterator()` in `Resources/config/services/services.php`, following
`src/CoreBundle/Resources/config/services/services.php:160`. `SyntaxWriterRegistry`,
`SyntaxReaderRegistry`, `ProfileRegistry` and `ValidatorRegistry` are the same shape. Tag constants
live on the interfaces via `#[AutoconfigureTag]`, following
`src/NotificationBundle/Configurator/ConfiguratorInterface.php:19`.

---

## 8. `EInvoiceDocument` — lifecycle, and why it is separate

### 8.1 Why a separate workflow — the written justification #2653 asks for

The `invoice` state machine in `config/packages/workflow.php` has eight places and drives payments,
reminders (`idx_invoice_reminder_scan` on `['due','status']`), the dashboard, reporting and the
datagrid filters. Three reasons a clearance state cannot join it:

1. **They are not the same state.** An invoice that has been sent, is overdue and is chasing payment
   may simultaneously have been rejected by a clearance platform for a missing identifier. Both facts
   are true at once. A single marking can hold only one.
2. **The graphs have different owners.** Invoice transitions are user actions and scheduled jobs.
   E-invoice transitions are mostly the answer of a remote platform, arriving asynchronously,
   possibly days later, possibly never. Merging them means a remote timeout can move an invoice out
   of `pending` and stop a reminder from firing.
3. **Cardinality differs.** One invoice can have several transmissions — a Factur-X PDF to the buyer
   and a Peppol transmission to a network, or a re-issue after a rejection. A marking on the invoice
   cannot represent "accepted by one channel, rejected by another".

`InvoiceStatus` also has a live example of what adding speculative places costs: `InvoiceStatus::Active`
is unreachable but still offered as a filter (SOL-42). Adding `pending_ksef`, `cleared`, `rejected_sdi`
to that enum would repeat the mistake at eight times the scale.

### 8.2 Entity

`src/EInvoiceBundle/Entity/EInvoiceDocument.php`, table `einvoice_document`. Not `final` — Doctrine
proxies extend entities.

Traits: `CompanyAware`, `TimeStampable`. **Not `Archivable`.** An audit record of what was
transmitted must not be soft-deletable; that is the point of §8.4. This is a deliberate departure
from the house default and is called out so nobody "fixes" it.

Source-document reference: nullable `invoice_id` and nullable `credit_note_id` with an
`#[ExactlyOneDocument]`-style constraint, following the established precedent at
`src/TaxBundle/Validator/Constraints/ExactlyOneDocumentValidator.php`. `CreditNote` does not exist
yet (SOL-69 / #2657); until it does, only `invoice_id` is mapped and the constraint asserts exactly
one of the mapped set. Both FKs are `onDelete: 'SET NULL'`, and the entity denormalises
`source_number` so the audit record survives a hard delete of its invoice. `RESTRICT` was considered
and rejected: it would make the existing `DELETE /api/invoices/{id}` operation fail for any invoice
that had ever been transmitted, which is a user-visible regression introduced by an audit concern.

### 8.3 Workflow

`Enum\EInvoiceStatus` (places) and `Enum\EInvoiceTransition` (transition names) — backed enums, per
the house rule. `InvoiceBundle\Model\Graph`'s class constants are legacy and are not a pattern to
copy. `EInvoiceStatus` implements `CoreBundle\Enum\HasStatusLabel`; if SOL-32's `StatusVariant` enum
has landed by then, it implements that instead — check before writing it.

Declared in `config/packages/workflow.php` as a fourth workflow:

```text
name:          einvoice_document
type:          state_machine
marking_store: method, property: statusValue     (same as invoice / quote / recurring_invoice)
audit_trail:   enabled                            (as #2676 requires)
supports:      EInvoiceDocument::class
```

| Transition | From | To |
|---|---|---|
| `queue` | `pending` | `queued` |
| `transmit` | `queued` | `transmitted` |
| `accept` | `transmitted` | `accepted` |
| `reject` | `transmitted` | `rejected` |
| `cancel` | `pending`, `queued`, `transmitted` | `cancelled` |
| `fail` | `pending`, `queued` | `failed` |

**`accepted`, `rejected`, `cancelled` and `failed` are terminal. There is no `retry` transition.**
This is a deliberate tightening of the wiki's `rejected ──► (correct, re-issue)` arrow. Reusing a
row for a retry would overwrite the payload that was rejected, and the reason for the rejection is
exactly the thing an audit trail needs to keep. A retry creates a **new** `EInvoiceDocument`. Every
row then holds one payload, one verdict, forever.

`failed` is local validation failure. A row is persisted at `pending` only once a payload has been
generated, so failed-validation rows are bounded by user-initiated generations.

### 8.4 Immutability — enforced, not documented

`#2676` requires this at the entity level, not by convention. Two mechanisms, both cheap:

```php
public function setPayload(string $payload): self
{
    $this->assertMutable();
    // …
}

private function assertMutable(): void
{
    if ($this->status->isTerminal()) {
        throw new DocumentAlreadyClearedException($this->id);
    }
}
```

Guarded setters on `payload`, `payloadHash`, `externalId` and `receipt`, plus
`Listener\ImmutablePayloadListener` on `preUpdate` asserting none of those four appear in the
changeset of a document whose marking is already terminal — catching writes that arrive through
hydration or reflection rather than the setters.

**Ordering trap, stated so it is not got backwards:** during the `accept` transition the marking is
still `transmitted` when `externalId` and `receipt` are written, so the write is allowed. The guard
only bites *after* the marking becomes terminal. Implement the check against the current marking, not
against the transition being applied.

### 8.5 Async dispatch

Messages in `Message/`: `TransmitEInvoiceDocument`, `PollEInvoiceDocument`. Routed to the existing
`async` transport in `config/packages/messenger.php`, inheriting its `max_retries: 3`, exponential
backoff and `failed` failure transport. No new transport.

Polling scan: a `CronBundle` command reading documents in `transmitted` whose `poll_after` has passed.

---

## 9. Feature gating — exactly one gate

| Capability | Gating |
|---|---|
| EN 16931 semantic model, mapping, UBL/CII generation | **Free.** No `FeatureGate` call. |
| Factur-X / ZUGFeRD PDF/A-3 | **Free.** |
| Validation, at every stage | **Free.** |
| Bring-your-own-credentials channels | **Free**, self-hosted and hosted alike. |
| SolidInvoice Cloud relay | **Paid**, hosted only. |

There is **one** gate in the entire programme, and it is not inside `EInvoiceBundle`.

`CloudRelayChannel` lives at `src/SaasBundle/Channel/CloudRelayChannel.php` and implements
`EInvoiceBundle\Channel\ChannelInterface`. `src/Kernel.php:74` registers `SolidInvoiceSaasBundle`
only when `AppMode::SAAS`, so on a self-hosted install the class is never wired and the channel is
simply absent from `ChannelRegistry`. Within `saas` mode it is additionally gated on a new
`Feature::CloudRelay` case with plan limits.

This placement is chosen over a `FeatureGate` check inside `EInvoiceBundle` for one reason: it makes
#2683's acceptance criterion — *"format generation and validation remain ungated, verified by a test
running with no subscription"* — structurally true rather than something a test has to police. There
is no gate to forget, because there is no gate.

Adding the relay means: a case in `src/SaasBundle/Feature/Feature.php`, the matching key in
`solidworx_platform.saas.features` (`platform.yaml`), plan limits, and copy in `FeatureCopyRegistry`.
`FeatureCatalogTest` asserts those stay in sync.

**Reversal cost, if the PM later wants the relay available to self-hosters as a paid add-on:** the
adapter moves to `src/EInvoiceBundle/Channel/Peppol/` and gains a `FeatureGate::isEnabled()` call in
`supports()`. One file, no interface change. Noting it so the decision is not treated as
irreversible.

---

## 10. Configuration, credentials and jurisdiction data

### 10.1 Per-company configuration

`Entity\EInvoiceConfiguration` — `CompanyAware`, `TimeStampable`: enabled channels, default profile,
jurisdiction, and the electronic addresses / EAS scheme codes from #2661 (SOL-90). Company-level
defaults are overridable per client and per invoice; the per-invoice override wins (#2671).

Non-secret settings that belong in the settings blob go through
`SettingsBundle\Config\ProviderInterface`, following `src/InvoiceBundle/Config/ConfigProvider.php`.
**Credentials never go in the settings blob.**

### 10.2 Credentials vault

```php
namespace SolidInvoice\EInvoiceBundle\Credentials;

interface CredentialVaultInterface
{
    public function get(string $channelIdentifier, string $key): ?string;
    public function put(string $channelIdentifier, string $key, string $value): void;
    public function forget(string $channelIdentifier, string $key): void;
}
```

No `Company` parameter. Scoping is implicit through the `CompanyAware` `EInvoiceCredential` entity
and `CompanyFilter`, exactly as every other tenant-scoped read in this application works. A company
parameter would be a second, parallel tenancy mechanism, and the one that gets forgotten is the one
that leaks.

`defuse/php-encryption ^2.4` — already required in `composer.json:32`. **A dedicated key, not
`APP_SECRET`.** `APP_SECRET` is itself a Defuse key generated at install
(`src/InstallBundle/Step/GenerateSecretStep.php:45`) and rotating it would invalidate unrelated
state, which makes #2678's key-rotation criterion unsatisfiable. Instead
`SOLIDINVOICE_EINVOICE_ENCRYPTION_KEY`, written through `CoreBundle\ConfigWriter` the same way
`APP_SECRET` is, with `einvoice:credentials:rotate-key` re-encrypting every row under a new key.

Hard requirements, each with a test (#2678): never logged, never rendered back into a form field,
never in any serialisation group, never in an exception message or trace, not readable across
companies.

### 10.3 Jurisdictions are data

`Resources/config/jurisdictions.php` returns an array keyed by ISO 3166-1 alpha-2, hydrated into
`Jurisdiction\JurisdictionDefinition` by `Jurisdiction\JurisdictionRegistry`:

```php
'DE' => [
    'syntax'            => SyntaxFormat::Cii,
    'defaultProfile'    => 'xrechnung-3.0.2',
    'mandatoryTerms'    => ['BT-10'],     // Leitweg-ID, B2G
    'identifierSchemes' => ['eas' => '9958'],
    'defaultChannel'    => 'peppol',
],
```

**Adding a country's rules must not require adding a class.** Only a genuinely new transmission
topology — a clearance platform with its own protocol — justifies a new `Channel` adapter. A country
that reuses Peppol is a data row and a `Profile`.

This is also what `#2671`'s "derive the suggested default from the buyer country" reads, so the
suggestion logic has one source rather than a `match` statement that drifts.

---

## 11. API, MCP and frontend surface

**API Platform.** `EInvoiceDocument` is exposed **read-only**: `GetCollection`, `Get`, and a
sub-resource `GET /invoices/{invoiceId}/e-invoice-documents` using the `Link` pattern from
`src/InvoiceBundle/Entity/Line.php`. Groups `einvoice_api:read` / `einvoice_api:write`, declared
explicitly on the entity per the house rule.

`payload` and `receipt` are **not** in the read group. They are unbounded in size and the receipt can
carry provider metadata; both are served by `Action\DownloadPayload` instead. Transmission is a state
change with side effects, so it goes through a `ProcessorInterface`, not a `Patch` on a field.

`EInvoiceCredential` has **no** API resource at all.

**MCP.** `Mcp/EInvoiceReadTools` only, for status. Transmission is not an MCP tool in Phase 1 —
AGENTS.md notes MCP is a *separate* entry point to the same domain, so any write tool would need its
own validation-mandatory guard. Read-only avoids that duplication until there is a reason for it.

**Frontend.** Tabler, per `.claude/skills/design-system.md`. Stimulus controllers under
`assets/`. The validation panel (#2674 / SOL-114) must show the three stages distinctly (§6.2) and
must link each violation to the field its BT code names. No hard-coded strings — `Resources/
translations/` (AGENTS.md §8). The UI surface itself is the Designer's, not this ADR's.

---

## 12. Schema and migration shape

A separate `schema` document on SOL-80 carries the column-level delta. The release-blocking
properties, stated here because they constrain the design:

- **The `EInvoiceBundle` migration is purely additive.** Three new tables (`einvoice_document`,
  `einvoice_credential`, `einvoice_configuration`); no column is altered, dropped or retyped on any
  existing table. Existing 3.0.x data is untouched; `down()` drops the three tables and is a genuine
  inverse. It is backwards-compatible and reversible.
- Written with the Schema tool, not raw SQL. `AbstractMySQLPlatform`, never `MySQLPlatform`, in the
  `isTransactional()` guard — `MariaDBPlatform` is a sibling, not a subclass, and `db-tests.yml`
  runs MariaDB 10.4–11.4 (copy `migrations/Version30100_3.php`).
- ULID primary keys with `UlidType` + `CustomIdGenerator(UlidGenerator::class)`.
- **`company_id` must not lead any index** — `CompanyFilter` decodes the literal rather than encoding
  the column, and a leading `company_id` plus a `HEX()` wrap is the non-sargable trap that file
  exists to avoid.
- **The file name is not fixed by this ADR.** Current highest is `migrations/Version30100_6.php`. If
  e-invoicing ships in 3.1.x it is `Version30100_7.php`; if it moves to 3.2.0 it is
  `Version30200_1.php`. That is a release-boundary question owned by the Release Manager and tracked
  on SOL-136 — check `migrations/` for the current highest part before writing it, since part
  numbers are not guaranteed contiguous.
- **Standing rule for the Epic A field additions (SOL-84 … SOL-95), which are the migrations with
  real 3.0.x data impact:** every new EN 16931 column is **nullable with no backfill**. A 3.0.x
  invoice genuinely has no BT-10 Leitweg-ID and no BT-49 electronic address; fabricating a default
  would produce an invoice that validates and is wrong, which is worse than one that fails validation
  with a message naming the field. The mapper's null rule (§4.3) and the validator's guard (§6.4)
  are what make nullable columns safe.

---

## 13. Test expectation

| Level | What | Where |
|---|---|---|
| Unit | Semantic model per business group; mapper per BG; null-not-default assertions | `Tests/Mapper/`, `Tests/Model/` |
| Unit | No `toFloat()` / `(float)` outside `Syntax/` | `Tests/Model/NoFloatConversionTest.php` |
| Unit | No `JohnWink\` symbol outside `Validation/Engine/` | `Tests/Validation/AdapterBoundaryTest.php` |
| Unit | Registry resolution and `UnknownChannelException` for each of the five registries | `Tests/{Channel,Syntax,Profile,Validation}/` |
| Unit | `ValidationReport::merge()`, outcome precedence, `NotValidated` never reports valid | `Tests/Validation/` |
| Unit | Severity-override map application, including `BR-CL-23` | `Tests/Validation/Engine/` |
| Unit | Workflow transitions **and every invalid transition**; terminal places have no exit | `Tests/Workflow/` |
| Unit | Immutability: mutating a terminal document's payload throws; writing `externalId` *during* `accept` does not | `Tests/Entity/EInvoiceDocumentTest.php` |
| Unit | Credential encrypt / decrypt / rotate | `Tests/Credentials/` |
| Functional | Cross-company isolation: documents and credentials invisible across companies | `Tests/Functional/` |
| Functional | Credentials absent from API responses, logs and exception traces | `Tests/Functional/` |
| Functional | Generation + validation succeed with **no subscription at all** | `Tests/Functional/` |
| Functional | Factur-X produced for an invoice with `invoice/watermark` at its default `'1'` | `InvoiceBundle/Tests/Functional/` |
| Conformance | KoSIT parity over documents **we** generate, plus the 127 negative mutations | `tests/Conformance/` |
| Conformance | veraPDF over generated Factur-X PDFs | CI (#2672) |

Fixtures: Foundry factories following `src/TaxBundle/Test/Factory/`. The EN 16931 / Peppol /
XRechnung conformance corpus is SOL-81 / #2654, already in flight.

Gates on every implementation issue: `bin/ecs check --fix && bin/phpstan analyse && bin/phpunit`,
plus `bin/rector process --dry-run`. PHPStan at level 6, no new baseline entries.

---

## 14. What this ADR does not close

Named rather than smoothed over. Each one blocks specific downstream work.

1. **No validation engine is selected for Peppol BIS Billing 3.0.** `john-wink/en16931-php` covers
   EN 16931 core and the German XRechnung CIUS. SOL-67 §8 states plainly that Peppol BIS 3.0 is not
   covered and the cost of implementing it against that engine was not evaluated. The official
   Peppol Schematron is XSLT 2.0 and libxslt cannot run it (measured). **This blocks the Peppol half
   of #2675 / SOL-115 and all of #2680 / SOL-119.** A spike is required and is being created as a
   blocker on both.
2. **The UBL writer library is not selected.** #2666 / SOL-103 owns it. SOL-68 explicitly declined
   to endorse `num-num/ubl-invoice` beyond "it produced conformant EN 16931 UBL for a minimal
   invoice"; its README says it is not UBL/Peppol feature-complete.
3. **veraPDF has never been run against an mpdf-generated PDF/A-3.** SOL-24's conformance criterion
   is unmet. Fonts are the specific risk: `Generator` sets `default_font => 'helvetica'`
   (`src/CoreBundle/Pdf/Generator.php:45`) and PDF/A requires every font embedded. #2672 / SOL-113
   must run before #2670 / SOL-107. If it fails unfixably, the fallback is
   `horstoeko/zugferd`'s `ZugferdDocumentPdfMerger` — a new dependency, so it escalates.
4. **Per-rule BT/BG coverage of the engine's `Violation::$flag` is unverified** (SOL-67 §5). #2674 /
   SOL-114 confirms it. `rules-reference.json` gives a static fallback for 237 of 274 rules.
   Naming trap for whoever implements it: in that JSON, `flag` means *severity*; on the `Violation`
   object, `flag` means *business term*.
5. **The release boundary is not decided.** Which release carries e-invoicing determines the
   migration version prefix (§12). Release Manager, SOL-136.
6. **`horstoeko/invoicesuite` as a facade may be reconsidered** at the multi-jurisdiction phases
   (#2684, #2686, #2688), against SOL-68 §6's four conditions — the first of which,
   `forwardCallWithCheckTo` throwing on an unknown method, is non-negotiable. Output was
   byte-identical, so a later switch is an adapter change. Note the option; do not design around it.

---

## 15. Decision log

| # | Decision | Alternative rejected |
|---|---|---|
| 1 | Adapter Boundary Rule, applied to every external capability | Per-component judgement — how designs drift |
| 2 | New `EInvoiceBundle`, `['all' => true]` | Extending `InvoiceBundle`; gating on `AppMode` |
| 3 | `CoreBundle` owns archival PDF mechanics; `EInvoiceBundle` owns Factur-X semantics | A `Packaging/` that talks to mpdf — two bundles owning one toolchain |
| 4 | `SyntaxWriterInterface::write()` returns `SyntaxDocument` | Returning `string` — forces callers to re-derive media type and filename |
| 5 | `ValidationOutcome` is a three-case enum | Nullable bool — conflates unvalidated with valid |
| 6 | Validators are staged and composite, short-circuiting after `Schema` | One monolithic validator |
| 7 | `ChannelInterface` excludes syntax types as well as vendor types | Invoice Ninja's `MutatorInterface` shape — cannot hold a clearance channel |
| 8 | Webhooks are a separate `WebhookCapableInterface` | `receive()` on `ChannelInterface` — dead method on most adapters |
| 9 | Separate `einvoice_document` workflow | Places on `InvoiceStatus` — cardinality and ownership both wrong |
| 10 | All four end places terminal; retry creates a new row | A `retry` transition — overwrites the payload that was rejected |
| 11 | `EInvoiceDocument` is not `Archivable` | House default — an audit record must not be soft-deletable |
| 12 | Nullable source FKs `SET NULL` + denormalised `source_number` | `RESTRICT` — breaks `DELETE /api/invoices/{id}` |
| 13 | Cloud relay adapter lives in `SaasBundle` | A `FeatureGate` check inside `EInvoiceBundle` — a gate that can be forgotten |
| 14 | Dedicated `SOLIDINVOICE_EINVOICE_ENCRYPTION_KEY` | Reusing `APP_SECRET` — makes rotation unsatisfiable |
| 15 | Vault takes no `Company` parameter | Explicit scoping — a second tenancy mechanism to forget |
| 16 | Jurisdictions are a data file | A class per country |
| 17 | Mapper never rounds; float conversion only in `Syntax/`, test-enforced | Convenience conversions — `BR-DEC-*` failures nobody can locate |
| 18 | `payload` / `receipt` excluded from API read groups | Exposing them — unbounded size, provider metadata |

---

## 16. Amendments

Append-only. Each entry corrects a section above; the decision text there is left as it was written
so the record of what was accepted on 2026-09-22 stays legible. Where an amendment and the section
it names disagree, **the amendment wins**.

| # | Corrects | What changed | Reasoning |
|---|---|---|---|
| 1 | §2.2, §11 | There is **no** `src/EInvoiceBundle/Resources/translations/`. Catalogs are app-level only. E-invoicing strings go in the existing `messages` domain, namespaced `einvoice.*`; no new `einvoice` domain. | `design` on SOL-83 §3.1; [#2655](https://github.com/SolidInvoice/SolidInvoice/issues/2655) *Scope amendments* §1 |
| 2 | §7.4, §13 | §7.4's "one shape, three times" does not hold for `ValidatorRegistry`. It exposes `all()` and `supporting(ProfileInterface, ValidationStage)`, not `get()`/`has()`/`all()`, and there is no `UnknownValidatorException`. §13's registry test row reads, for this registry, as "`supporting()` on an empty registry returns `[]`". | `design` on SOL-83 §3.2; [#2655](https://github.com/SolidInvoice/SolidInvoice/issues/2655) *Scope amendments* §2 |
| 3 | §12 | The `EInvoiceBundle` migration does **not** create `einvoice_document`, `einvoice_credential` and `einvoice_configuration` together. `einvoice_document` lands on its own, additive migration; `einvoice_credential` and `einvoice_configuration` land on their own issues (#2678, #2661) with their own migrations. Creating tables with no mapping behind them would leave `doctrine:schema:validate` reporting the schema out of sync — the opposite of the intent. | `design` on SOL-89 §8.1 |
| 4 | §12 | "`company_id` must not lead any index", read literally, is already violated by `credit_notes` (`migrations/Version30100_7.php`, `addIndex(['company_id','status'])`). The rule's actual target is composite *query-serving* indexes led by `company_id`, which `CompanyFilter`'s SQLite `HEX()` fallback disqualifies from using an index. A **single-column FK-backing index on `company_id` is required, not prohibited** — PostgreSQL does not auto-index foreign-key columns, so without it every cascade and every tenant-scoped scan is a sequential scan. `einvoice_document` gets `['company_id']` alone, no composite led by it. | `design` on SOL-89 §8.2 |
| 5 | §8.5 | `poll_after` is not deferred to #2679. It lands on `einvoice_document` now, nullable and unused by this issue, because adding it later would cost a second migration on a table this issue is creating, and the polling index `['status','poll_after']` would have to be rebuilt with it. | `design` on SOL-89 §8.3 |
| 6 | §8.2 | The `CreditNote`-existence conditional resolves: both `invoice_id` and `credit_note_id` are mapped on `einvoice_document`, not `invoice_id` alone. `CreditNote` exists as of `c4179caef` (SOL-69/#2657), so the "until it does, only `invoice_id` is mapped" clause no longer applies. | `design` on SOL-89 §3.3, §8.4 |

### Amendment 1 is a defect, not a preference

§2.2 and §11 are not merely inconsistent with house convention — following them would ship an
untranslatable feature, silently.

Verified in this tree:

- `find src -type d -name translations` returns nothing. No bundle in this repository has one.
- The catalogs are `translations/{messages,email,validators}.en.yml` — three files.
- `AGENTS.md:388-389`: catalogs are app-level, one file per domain+locale, and are **not** split per
  bundle.
- `.github/workflows/translations-pull.yml:67-68` sets `add-paths: translations/**`.
- `config/packages/translation.php:36` pins the pushed domain set to `['messages', 'email',
  'validators']`.

A bundle-level catalog sits outside the pull path; a new `einvoice` domain sits outside the pushed
set. Either loads fine at runtime, is sent to no translator, and returns to every non-English user
in English permanently — in a feature whose entire purpose is cross-border compliance across the EU,
LatAm and APAC. Nothing fails; it just never gets translated.

§11's own citation is part of the defect: it writes "No hard-coded strings — `Resources/
translations/` (AGENTS.md §8)", where AGENTS.md §8 says the opposite of what it is cited for.
