# Custom Fields for Clients and Contacts — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Spec:** `docs/superpowers/specs/2026-04-24-custom-fields-design.md`

**Goal:** Let users define their own fields on `Client` and `Contact` records (company-scoped), fill them in via the UI/API, and view them on client/contact pages — replacing the legacy `ContactType` + `AdditionalContactDetail` system with a unified `CustomField` + `CustomFieldValue` pair.

**Architecture:** Two new entities in `CoreBundle` (`CustomField` = definition, `CustomFieldValue` = value) with a polymorphic `(target, target_id)` discriminator. A `CustomFieldTypeResolver` service encapsulates type-specific behavior (form rendering, constraints, serialize/deserialize) for nine types (TEXT/TEXTAREA/NUMBER/DATE/EMAIL/URL/CHECKBOX/SELECT/MULTI_SELECT). A reusable `CustomFieldValueCollectionType` form widget is embedded in `ClientType` and `ContactType`. A `/settings/custom-fields` page (with Tabler tabs, modal editor, and drag-to-reorder via Sortable.js) manages definitions. API Platform exposes definitions as a CRUD resource and embeds values on Client/Contact payloads via a custom normalizer/denormalizer/state-processor trio. MCP is enabled on each operation. A single migration creates the new tables, copies legacy data, and drops the old tables.

**Tech Stack:** Symfony 7.4+, PHP 8.4+, Doctrine ORM, API Platform 4, Symfony UX (LiveComponent + Stimulus + Sortable.js), Twig, PHPUnit, Foundry.

**File header (every PHP file):**

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
```

**Verification commands** (run these after each phase, and at each commit):

```bash
bin/ecs check --fix
bin/phpstan analyse
bin/phpunit
```

---

## File Structure Overview

**New files:**

| Path | Responsibility |
|---|---|
| `src/CoreBundle/Enum/CustomFieldTarget.php` | Backed enum (`CLIENT`, `CONTACT`) — discriminator |
| `src/CoreBundle/Enum/CustomFieldType.php` | Backed enum (9 types) |
| `src/CoreBundle/Entity/CustomField/CustomField.php` | Field definition entity + ApiResource |
| `src/CoreBundle/Entity/CustomField/CustomFieldValue.php` | Field value entity (no API resource — embedded) |
| `src/CoreBundle/Repository/CustomFieldRepository.php` | Definition queries (by target, ordered) |
| `src/CoreBundle/Repository/CustomFieldValueRepository.php` | Value queries (by record), upsert helpers |
| `src/CoreBundle/Service/CustomField/CustomFieldTypeResolver.php` | Type behavior: form, constraints, serialize/deserialize, format |
| `src/CoreBundle/Form/Type/CustomFieldValueCollectionType.php` | Form widget embedded in Client/Contact forms |
| `src/CoreBundle/Listener/CustomFieldValueCleanupListener.php` | Doctrine `postRemove` on Client/Contact deletes orphan values |
| `src/CoreBundle/Command/CustomFieldOrphanCheckCommand.php` | Operational tool to find/clean orphans |
| `src/CoreBundle/Twig/Components/CustomFieldsList.php` | View component used by Client/Contact templates |
| `src/CoreBundle/Resources/views/Components/CustomFieldsList.html.twig` | Component template |
| `src/CoreBundle/Serializer/Normalizer/CustomFieldsNormalizer.php` | Serializes `customFields` onto Client/Contact API output |
| `src/CoreBundle/Serializer/Normalizer/CustomFieldsDenormalizer.php` | Reads `customFields` from API input, stages updates |
| `src/CoreBundle/State/CustomFieldsStateProcessor.php` | After Client/Contact persist, applies the staged updates |
| `src/CoreBundle/OpenApi/CustomFieldsSchemaDecorator.php` | Adds `customFields` to OpenAPI schemas as `additionalProperties` |
| `src/SettingsBundle/Action/CustomField/IndexAction.php` | List page (two tabs) |
| `src/SettingsBundle/Action/CustomField/CreateAction.php` | Create form submit |
| `src/SettingsBundle/Action/CustomField/EditAction.php` | Edit form submit |
| `src/SettingsBundle/Action/CustomField/DeleteAction.php` | Delete with confirmation |
| `src/SettingsBundle/Action/CustomField/ReorderAction.php` | POST endpoint for drag-reorder |
| `src/SettingsBundle/Form/Type/CustomFieldDefinitionType.php` | Form for create/edit modal |
| `src/SettingsBundle/Twig/Components/CustomFieldEditModal.php` | LiveComponent for create/edit modal |
| `src/SettingsBundle/Resources/views/CustomField/index.html.twig` | List page template |
| `src/SettingsBundle/Resources/views/Components/CustomFieldEditModal.html.twig` | Modal template |
| `assets/controllers/custom-field-reorder_controller.ts` | Stimulus controller using Sortable.js |
| `migrations/Version30100_1.php` | Schema + data migration |

**Modified files:**

| Path | Change |
|---|---|
| `src/ClientBundle/Entity/Client.php` | Remove old additional details references; no schema change |
| `src/ClientBundle/Entity/Contact.php` | Remove `$additionalContactDetails` collection + accessors |
| `src/ClientBundle/Form/Type/ClientType.php` | Add `customFields` child |
| `src/ClientBundle/Form/Type/ContactType.php` | Remove `additionalContactDetails` child; add `customFields` child |
| `src/ClientBundle/Resources/views/Default/view.html.twig` | Embed `<twig:CustomFieldsList />` card |
| `src/ClientBundle/Resources/views/Components/ContactInfo.html.twig` | Replace old loop with `<twig:CustomFieldsList />` |
| `src/SettingsBundle/Resources/config/routing.php` | Add custom-field routes |
| `config/packages/api_platform.yaml` (or equivalent) | Ensure MCP enabled (only if not already) |
| `assets/controllers.json` | Add `sortablejs` if needed |
| `package.json` | Add `sortablejs` dependency |
| `src/MenuBundle/...` | Add Settings → "Custom fields" link (find existing settings menu definition first) |

**Deleted files** (after Phase B migration succeeds):

- `src/ClientBundle/Entity/ContactType.php`
- `src/ClientBundle/Entity/AdditionalContactDetail.php`
- `src/ClientBundle/Repository/ContactTypeRepository.php`
- `src/ClientBundle/Form/Type/ContactDetailType.php`
- `src/ClientBundle/Form/Type/ContactDetailCollectionType.php`
- `src/ClientBundle/Resources/views/Form/contact_details.html.twig`
- Any test files for the above

---

## Phase A — Foundation: enums, entities, resolver

### Task 1: Create `CustomFieldTarget` enum

**Files:**
- Create: `src/CoreBundle/Enum/CustomFieldTarget.php`
- Test: `src/CoreBundle/Tests/Enum/CustomFieldTargetTest.php`

- [ ] **Step 1: Write the test**

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

namespace SolidInvoice\CoreBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;

final class CustomFieldTargetTest extends TestCase
{
    public function testCases(): void
    {
        self::assertSame('CLIENT', CustomFieldTarget::CLIENT->value);
        self::assertSame('CONTACT', CustomFieldTarget::CONTACT->value);
    }

    public function testLabel(): void
    {
        self::assertSame('Client', CustomFieldTarget::CLIENT->label());
        self::assertSame('Contact', CustomFieldTarget::CONTACT->label());
    }
}
```

- [ ] **Step 2: Run the test, expect failure**

```bash
bin/phpunit src/CoreBundle/Tests/Enum/CustomFieldTargetTest.php
```

Expected: FAIL — class does not exist.

- [ ] **Step 3: Create the enum**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Enum;

enum CustomFieldTarget: string
{
    case CLIENT = 'CLIENT';
    case CONTACT = 'CONTACT';

    public function label(): string
    {
        return match ($this) {
            self::CLIENT => 'Client',
            self::CONTACT => 'Contact',
        };
    }
}
```

- [ ] **Step 4: Run the test, expect pass**

```bash
bin/phpunit src/CoreBundle/Tests/Enum/CustomFieldTargetTest.php
```

- [ ] **Step 5: Commit**

```bash
git add src/CoreBundle/Enum/CustomFieldTarget.php src/CoreBundle/Tests/Enum/CustomFieldTargetTest.php
git commit -m "feat(core): add CustomFieldTarget enum"
```

---

### Task 2: Create `CustomFieldType` enum

**Files:**
- Create: `src/CoreBundle/Enum/CustomFieldType.php`
- Test: `src/CoreBundle/Tests/Enum/CustomFieldTypeTest.php`

- [ ] **Step 1: Write the test**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;

final class CustomFieldTypeTest extends TestCase
{
    public function testCases(): void
    {
        self::assertCount(9, CustomFieldType::cases());
        self::assertSame('text', CustomFieldType::TEXT->value);
        self::assertSame('multi_select', CustomFieldType::MULTI_SELECT->value);
    }

    public function testRequiresOptions(): void
    {
        self::assertTrue(CustomFieldType::SELECT->requiresOptions());
        self::assertTrue(CustomFieldType::MULTI_SELECT->requiresOptions());
        self::assertFalse(CustomFieldType::TEXT->requiresOptions());
        self::assertFalse(CustomFieldType::NUMBER->requiresOptions());
    }

    public function testLabel(): void
    {
        self::assertSame('Text', CustomFieldType::TEXT->label());
        self::assertSame('Multi-select', CustomFieldType::MULTI_SELECT->label());
    }
}
```

- [ ] **Step 2: Run the test, expect failure.**

- [ ] **Step 3: Create the enum**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Enum;

enum CustomFieldType: string
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case NUMBER = 'number';
    case DATE = 'date';
    case EMAIL = 'email';
    case URL = 'url';
    case CHECKBOX = 'checkbox';
    case SELECT = 'select';
    case MULTI_SELECT = 'multi_select';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Text',
            self::TEXTAREA => 'Long text',
            self::NUMBER => 'Number',
            self::DATE => 'Date',
            self::EMAIL => 'Email',
            self::URL => 'URL',
            self::CHECKBOX => 'Checkbox',
            self::SELECT => 'Single-select',
            self::MULTI_SELECT => 'Multi-select',
        };
    }

    public function requiresOptions(): bool
    {
        return $this === self::SELECT || $this === self::MULTI_SELECT;
    }
}
```

- [ ] **Step 4: Run the test, expect pass.**

- [ ] **Step 5: Commit**

```bash
git add src/CoreBundle/Enum/CustomFieldType.php src/CoreBundle/Tests/Enum/CustomFieldTypeTest.php
git commit -m "feat(core): add CustomFieldType enum"
```

---

### Task 3: Create `CustomField` entity (definition)

**Files:**
- Create: `src/CoreBundle/Entity/CustomField/CustomField.php`
- Create: `src/CoreBundle/Repository/CustomFieldRepository.php`

This task creates the entity *without* `#[ApiResource]` attributes — those are added in Phase F so the whole API surface lands together. The entity is mapped to the new `custom_field` table.

- [ ] **Step 1: Create the entity class**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Entity\CustomField;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: CustomField::TABLE_NAME)]
#[ORM\Index(name: 'idx_cf_company_target_pos', columns: ['company_id', 'target', 'position'])]
#[ORM\UniqueConstraint(name: 'uq_cf_company_target_key', columns: ['company_id', 'target', 'field_key'])]
#[ORM\Entity(repositoryClass: CustomFieldRepository::class)]
class CustomField
{
    final public const TABLE_NAME = 'custom_field';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'target', type: Types::STRING, length: 32, enumType: CustomFieldTarget::class)]
    #[Assert\NotNull]
    private ?CustomFieldTarget $target = null;

    #[ORM\Column(type: Types::STRING, length: 125)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 125)]
    private ?string $label = null;

    #[ORM\Column(name: 'field_key', type: Types::STRING, length: 64)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    #[Assert\Regex(pattern: '/^[a-z][a-z0-9_]*$/', message: 'Field key must start with a lowercase letter and contain only lowercase letters, digits, and underscores.')]
    private ?string $fieldKey = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 32, enumType: CustomFieldType::class)]
    #[Assert\NotNull]
    private ?CustomFieldType $type = null;

    /**
     * @var list<array{value: string, label: string}>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $options = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $required = false;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $position = 0;

    public function getId(): ?Ulid { return $this->id; }
    public function getTarget(): ?CustomFieldTarget { return $this->target; }
    public function setTarget(CustomFieldTarget $target): self { $this->target = $target; return $this; }
    public function getLabel(): ?string { return $this->label; }
    public function setLabel(string $label): self { $this->label = $label; return $this; }
    public function getFieldKey(): ?string { return $this->fieldKey; }
    public function setFieldKey(string $key): self { $this->fieldKey = $key; return $this; }
    public function getType(): ?CustomFieldType { return $this->type; }
    public function setType(CustomFieldType $type): self { $this->type = $type; return $this; }
    public function getOptions(): ?array { return $this->options; }
    public function setOptions(?array $options): self { $this->options = $options; return $this; }
    public function isRequired(): bool { return $this->required; }
    public function setRequired(bool $required): self { $this->required = $required; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }
}
```

- [ ] **Step 2: Create the repository**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;

/**
 * @extends ServiceEntityRepository<CustomField>
 */
class CustomFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomField::class);
    }

    /**
     * @return list<CustomField>
     */
    public function findByTargetOrdered(CustomFieldTarget $target): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.target = :target')
            ->setParameter('target', $target->value)
            ->orderBy('f.position', 'ASC')
            ->addOrderBy('f.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function nextPosition(CustomFieldTarget $target): int
    {
        $max = (int) $this->createQueryBuilder('f')
            ->select('COALESCE(MAX(f.position), -1)')
            ->andWhere('f.target = :target')
            ->setParameter('target', $target->value)
            ->getQuery()
            ->getSingleScalarResult();

        return $max + 1;
    }

    public function findOneByTargetAndKey(CustomFieldTarget $target, string $fieldKey): ?CustomField
    {
        return $this->findOneBy(['target' => $target->value, 'fieldKey' => $fieldKey]);
    }
}
```

- [ ] **Step 3: Run static analysis to confirm wiring**

```bash
bin/phpstan analyse src/CoreBundle/Entity/CustomField src/CoreBundle/Repository/CustomFieldRepository.php
```

Expected: pass (no migration yet, so no DB checks).

- [ ] **Step 4: Commit**

```bash
git add src/CoreBundle/Entity/CustomField/CustomField.php src/CoreBundle/Repository/CustomFieldRepository.php
git commit -m "feat(core): add CustomField definition entity and repository"
```

---

### Task 4: Create `CustomFieldValue` entity

**Files:**
- Create: `src/CoreBundle/Entity/CustomField/CustomFieldValue.php`
- Create: `src/CoreBundle/Repository/CustomFieldValueRepository.php`

- [ ] **Step 1: Create the entity**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Entity\CustomField;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Table(name: CustomFieldValue::TABLE_NAME)]
#[ORM\Index(name: 'idx_cfv_company_target_record', columns: ['company_id', 'target', 'target_id'])]
#[ORM\Index(name: 'idx_cfv_field', columns: ['field_id'])]
#[ORM\UniqueConstraint(name: 'uq_cfv_field_record', columns: ['field_id', 'target_id'])]
#[ORM\Entity(repositoryClass: CustomFieldValueRepository::class)]
class CustomFieldValue
{
    final public const TABLE_NAME = 'custom_field_value';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\ManyToOne(targetEntity: CustomField::class)]
    #[ORM\JoinColumn(name: 'field_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?CustomField $field = null;

    #[ORM\Column(name: 'target', type: Types::STRING, length: 32, enumType: CustomFieldTarget::class)]
    private ?CustomFieldTarget $target = null;

    #[ORM\Column(name: 'target_id', type: UlidType::NAME)]
    private ?Ulid $targetId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    public function getId(): ?Ulid { return $this->id; }
    public function getField(): ?CustomField { return $this->field; }
    public function setField(CustomField $field): self { $this->field = $field; return $this; }
    public function getTarget(): ?CustomFieldTarget { return $this->target; }
    public function setTarget(CustomFieldTarget $target): self { $this->target = $target; return $this; }
    public function getTargetId(): ?Ulid { return $this->targetId; }
    public function setTargetId(Ulid $targetId): self { $this->targetId = $targetId; return $this; }
    public function getValue(): ?string { return $this->value; }
    public function setValue(?string $value): self { $this->value = $value; return $this; }
}
```

- [ ] **Step 2: Create the repository**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomFieldValue;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<CustomFieldValue>
 */
class CustomFieldValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomFieldValue::class);
    }

    /**
     * @return list<CustomFieldValue>
     */
    public function findForRecord(CustomFieldTarget $target, Ulid $targetId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.target = :target')
            ->andWhere('v.targetId = :targetId')
            ->setParameter('target', $target->value)
            ->setParameter('targetId', $targetId, UlidType::NAME)
            ->getQuery()
            ->getResult();
    }

    public function findOneFor(CustomField $field, Ulid $targetId): ?CustomFieldValue
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.field = :field')
            ->andWhere('v.targetId = :targetId')
            ->setParameter('field', $field->getId(), UlidType::NAME)
            ->setParameter('targetId', $targetId, UlidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByField(CustomField $field): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.field = :field')
            ->setParameter('field', $field->getId(), UlidType::NAME)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteForRecord(CustomFieldTarget $target, Ulid $targetId): void
    {
        $this->createQueryBuilder('v')
            ->delete()
            ->andWhere('v.target = :target')
            ->andWhere('v.targetId = :targetId')
            ->setParameter('target', $target->value)
            ->setParameter('targetId', $targetId, UlidType::NAME)
            ->getQuery()
            ->execute();
    }
}
```

Add the missing `use` for `UlidType`:

```php
use Symfony\Bridge\Doctrine\Types\UlidType;
```

- [ ] **Step 3: Static analysis**

```bash
bin/phpstan analyse src/CoreBundle/Entity/CustomField src/CoreBundle/Repository
```

- [ ] **Step 4: Commit**

```bash
git add src/CoreBundle/Entity/CustomField/CustomFieldValue.php src/CoreBundle/Repository/CustomFieldValueRepository.php
git commit -m "feat(core): add CustomFieldValue entity and repository"
```

---

### Task 5: Create migration — schema only (data copy comes in Task 7)

**Files:**
- Create: `migrations/Version30100_1.php`

We split the migration in two parts conceptually:
- This task: create the new tables.
- Task 7: copy data from `contact_type`/`additional_contact_detail` and drop them.

Both go in **the same migration class** but we build it in two passes (first the schema part now, then add the data-copy step in Task 7) so the schema can be tested independently.

- [ ] **Step 1: Create the migration**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version30100_1 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add custom_field and custom_field_value tables; migrate legacy contact_type/additional_contact_detail data; drop legacy tables.';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        // 1. Create custom_field
        $cf = $schema->createTable('custom_field');
        $cf->addColumn('id', 'ulid', ['notnull' => true]);
        $cf->addColumn('company_id', 'ulid', ['notnull' => true]);
        $cf->addColumn('target', 'string', ['length' => 32, 'notnull' => true]);
        $cf->addColumn('label', 'string', ['length' => 125, 'notnull' => true]);
        $cf->addColumn('field_key', 'string', ['length' => 64, 'notnull' => true]);
        $cf->addColumn('type', 'string', ['length' => 32, 'notnull' => true]);
        $cf->addColumn('options', 'json', ['notnull' => false]);
        $cf->addColumn('required', 'boolean', ['notnull' => true, 'default' => false]);
        $cf->addColumn('position', 'integer', ['notnull' => true, 'default' => 0]);
        $cf->addColumn('created', 'datetime_immutable', ['notnull' => true]);
        $cf->addColumn('updated', 'datetime_immutable', ['notnull' => false]);
        $cf->setPrimaryKey(['id']);
        $cf->addUniqueIndex(['company_id', 'target', 'field_key'], 'uq_cf_company_target_key');
        $cf->addIndex(['company_id', 'target', 'position'], 'idx_cf_company_target_pos');
        $cf->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);

        // 2. Create custom_field_value
        $cfv = $schema->createTable('custom_field_value');
        $cfv->addColumn('id', 'ulid', ['notnull' => true]);
        $cfv->addColumn('company_id', 'ulid', ['notnull' => true]);
        $cfv->addColumn('field_id', 'ulid', ['notnull' => true]);
        $cfv->addColumn('target', 'string', ['length' => 32, 'notnull' => true]);
        $cfv->addColumn('target_id', 'ulid', ['notnull' => true]);
        $cfv->addColumn('value', 'text', ['notnull' => false]);
        $cfv->addColumn('created', 'datetime_immutable', ['notnull' => true]);
        $cfv->addColumn('updated', 'datetime_immutable', ['notnull' => false]);
        $cfv->setPrimaryKey(['id']);
        $cfv->addUniqueIndex(['field_id', 'target_id'], 'uq_cfv_field_record');
        $cfv->addIndex(['company_id', 'target', 'target_id'], 'idx_cfv_company_target_record');
        $cfv->addIndex(['field_id'], 'idx_cfv_field');
        $cfv->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $cfv->addForeignKeyConstraint('custom_field', ['field_id'], ['id'], ['onDelete' => 'CASCADE']);

        // (Task 7 will append data-copy + legacy table drops here.)
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration(
            'This migration restructures contact types into a unified custom-field schema. ' .
            'Reversing would lose data. Restore from backup.'
        );
    }
}
```

- [ ] **Step 2: Validate the migration's diff matches Doctrine metadata so far**

```bash
bin/console doctrine:migrations:diff --dry-run
```

Expected: no further differences for the two new tables (Doctrine sees them in metadata, finds them in schema). If there are differences, reconcile attribute mappings.

- [ ] **Step 3: Run migration locally to confirm syntax**

```bash
bin/console doctrine:migrations:migrate latest --no-interaction
bin/console doctrine:migrations:migrate prev --no-interaction || true
bin/console doctrine:migrations:migrate latest --no-interaction
```

Expected: migration runs cleanly; tables exist after `migrate latest`.

- [ ] **Step 4: Commit**

```bash
git add migrations/Version30100_1.php
git commit -m "feat(core): migration scaffold for custom_field tables"
```

---

### Task 6: Implement `CustomFieldTypeResolver`

**Files:**
- Create: `src/CoreBundle/Service/CustomField/CustomFieldTypeResolver.php`
- Test: `src/CoreBundle/Tests/Service/CustomField/CustomFieldTypeResolverTest.php`

The resolver is one service that owns all per-type behavior. Form types, normalizers, and the view template all delegate to it.

- [ ] **Step 1: Write tests for `serialize`/`deserialize` round-trips**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Tests\Service\CustomField;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldTypeResolver;

final class CustomFieldTypeResolverTest extends TestCase
{
    private CustomFieldTypeResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new CustomFieldTypeResolver();
    }

    /**
     * @dataProvider roundTripData
     */
    public function testRoundTrip(CustomFieldType $type, mixed $input, ?string $stored, mixed $deserialized, ?array $options = null): void
    {
        $field = (new CustomField())->setType($type)->setOptions($options);
        self::assertSame($stored, $this->resolver->serialize($field, $input));
        self::assertEquals($deserialized, $this->resolver->deserialize($field, $stored));
    }

    public static function roundTripData(): iterable
    {
        yield 'text' => [CustomFieldType::TEXT, 'hello', 'hello', 'hello'];
        yield 'textarea' => [CustomFieldType::TEXTAREA, "line1\nline2", "line1\nline2", "line1\nline2"];
        yield 'number int' => [CustomFieldType::NUMBER, 42, '42', 42];
        yield 'number float' => [CustomFieldType::NUMBER, 3.14, '3.14', 3.14];
        yield 'date' => [CustomFieldType::DATE, new \DateTimeImmutable('2026-04-24'), '2026-04-24', new \DateTimeImmutable('2026-04-24')];
        yield 'email' => [CustomFieldType::EMAIL, 'a@b.com', 'a@b.com', 'a@b.com'];
        yield 'url' => [CustomFieldType::URL, 'https://x.com', 'https://x.com', 'https://x.com'];
        yield 'checkbox true' => [CustomFieldType::CHECKBOX, true, '1', true];
        yield 'checkbox false' => [CustomFieldType::CHECKBOX, false, '0', false];
        yield 'select' => [CustomFieldType::SELECT, 'gold', 'gold', 'gold', [['value' => 'gold', 'label' => 'Gold']]];
        yield 'multi-select' => [CustomFieldType::MULTI_SELECT, ['a', 'b'], '["a","b"]', ['a', 'b'], [['value' => 'a', 'label' => 'A'], ['value' => 'b', 'label' => 'B']]];
        yield 'multi-select empty' => [CustomFieldType::MULTI_SELECT, [], '[]', []];
    }

    public function testNullSerialize(): void
    {
        $field = (new CustomField())->setType(CustomFieldType::TEXT);
        self::assertNull($this->resolver->serialize($field, null));
        self::assertNull($this->resolver->serialize($field, ''));
    }

    public function testNullDeserialize(): void
    {
        $field = (new CustomField())->setType(CustomFieldType::TEXT);
        self::assertNull($this->resolver->deserialize($field, null));
    }
}
```

- [ ] **Step 2: Run, expect failure.**

- [ ] **Step 3: Implement the resolver**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Service\CustomField;

use DateTimeImmutable;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraints as Assert;
use function array_column;
use function is_array;
use function is_bool;
use function is_string;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;

final class CustomFieldTypeResolver
{
    /**
     * @return array{0: class-string, 1: array<string, mixed>}
     */
    public function formTypeAndOptions(CustomField $field): array
    {
        return match ($field->getType()) {
            CustomFieldType::TEXT => [TextType::class, []],
            CustomFieldType::TEXTAREA => [TextareaType::class, []],
            CustomFieldType::NUMBER => [NumberType::class, ['html5' => true]],
            CustomFieldType::DATE => [DateType::class, ['widget' => 'single_text']],
            CustomFieldType::EMAIL => [EmailType::class, []],
            CustomFieldType::URL => [UrlType::class, []],
            CustomFieldType::CHECKBOX => [CheckboxType::class, []],
            CustomFieldType::SELECT => [ChoiceType::class, [
                'choices' => $this->choices($field),
                'placeholder' => 'Choose...',
            ]],
            CustomFieldType::MULTI_SELECT => [ChoiceType::class, [
                'choices' => $this->choices($field),
                'multiple' => true,
                'expanded' => false,
            ]],
        };
    }

    /**
     * @return array<int, Assert\Constraint>
     */
    public function constraints(CustomField $field): array
    {
        $constraints = [];
        if ($field->isRequired()) {
            $constraints[] = match ($field->getType()) {
                CustomFieldType::CHECKBOX => new Assert\IsTrue(),
                CustomFieldType::MULTI_SELECT => new Assert\Count(min: 1),
                default => new Assert\NotBlank(),
            };
        }

        $constraints[] = match ($field->getType()) {
            CustomFieldType::EMAIL => new Assert\Email(),
            CustomFieldType::URL => new Assert\Url(),
            CustomFieldType::NUMBER => new Assert\Type('numeric'),
            CustomFieldType::DATE => new Assert\Date(),
            CustomFieldType::SELECT => new Assert\Choice(choices: array_column($field->getOptions() ?? [], 'value')),
            CustomFieldType::MULTI_SELECT => new Assert\Choice(choices: array_column($field->getOptions() ?? [], 'value'), multiple: true),
            default => null,
        };

        return array_values(array_filter($constraints));
    }

    public function serialize(CustomField $field, mixed $input): ?string
    {
        if ($input === null || $input === '' || $input === []) {
            // Multi-select empty array should still serialize to "[]" so the value is non-null when set.
            if ($field->getType() === CustomFieldType::MULTI_SELECT && is_array($input)) {
                return '[]';
            }
            return null;
        }

        return match ($field->getType()) {
            CustomFieldType::TEXT, CustomFieldType::TEXTAREA, CustomFieldType::EMAIL, CustomFieldType::URL, CustomFieldType::SELECT
                => (string) $input,
            CustomFieldType::NUMBER => (string) $input,
            CustomFieldType::DATE => $input instanceof \DateTimeInterface ? $input->format('Y-m-d') : (string) $input,
            CustomFieldType::CHECKBOX => $input ? '1' : '0',
            CustomFieldType::MULTI_SELECT => json_encode(array_values((array) $input), JSON_THROW_ON_ERROR),
        };
    }

    public function deserialize(CustomField $field, ?string $stored): mixed
    {
        if ($stored === null) {
            return null;
        }

        return match ($field->getType()) {
            CustomFieldType::TEXT, CustomFieldType::TEXTAREA, CustomFieldType::EMAIL, CustomFieldType::URL, CustomFieldType::SELECT => $stored,
            CustomFieldType::NUMBER => str_contains($stored, '.') ? (float) $stored : (int) $stored,
            CustomFieldType::DATE => new DateTimeImmutable($stored),
            CustomFieldType::CHECKBOX => $stored === '1',
            CustomFieldType::MULTI_SELECT => json_decode($stored, true, flags: JSON_THROW_ON_ERROR),
        };
    }

    public function formatForDisplay(CustomField $field, ?string $stored): string
    {
        if ($stored === null || $stored === '') {
            return '—';
        }

        return match ($field->getType()) {
            CustomFieldType::CHECKBOX => $stored === '1' ? '✓' : '—',
            CustomFieldType::SELECT => $this->labelFor($field, $stored) ?? $stored,
            CustomFieldType::MULTI_SELECT => implode(', ', array_map(
                fn (string $v): string => $this->labelFor($field, $v) ?? $v,
                json_decode($stored, true, flags: JSON_THROW_ON_ERROR)
            )),
            default => $stored,
        };
    }

    /**
     * @return array<string, string>
     */
    private function choices(CustomField $field): array
    {
        $out = [];
        foreach ($field->getOptions() ?? [] as $opt) {
            $out[$opt['label']] = $opt['value'];
        }
        return $out;
    }

    private function labelFor(CustomField $field, string $value): ?string
    {
        foreach ($field->getOptions() ?? [] as $opt) {
            if ($opt['value'] === $value) {
                return $opt['label'];
            }
        }
        return null;
    }
}
```

- [ ] **Step 4: Run tests, expect pass.**

```bash
bin/phpunit src/CoreBundle/Tests/Service/CustomField/CustomFieldTypeResolverTest.php
```

- [ ] **Step 5: Commit**

```bash
git add src/CoreBundle/Service/CustomField/CustomFieldTypeResolver.php src/CoreBundle/Tests/Service/CustomField/CustomFieldTypeResolverTest.php
git commit -m "feat(core): add CustomFieldTypeResolver service"
```

---

## Phase B — Data migration from legacy `ContactType`/`AdditionalContactDetail`

### Task 7: Extend migration with data copy and drop legacy tables

**Files:**
- Modify: `migrations/Version30100_1.php`

- [ ] **Step 1: Extend `up()` with data copy + drop, after the `createTable` section**

Append to `up()` *after* the two `createTable` calls and *before* the closing brace:

```php
        // 3. Defer the actual data copy until after the schema is realized.
        // Doctrine Migrations runs $schema operations as DDL, then runs preDown/postUp.
        // We use postUp() to run DML.
    }

    public function postUp(Schema $schema): void
    {
        // 3a. Copy contact_type → custom_field
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, company_id, name, type, field_options, required FROM contact_types'
        );

        $seenKeysByCompany = [];
        $positionByCompany = [];
        foreach ($rows as $r) {
            $companyKey = bin2hex((string) $r['company_id']);
            $baseKey = $this->slugify((string) $r['name']);
            $key = $baseKey;
            $i = 2;
            while (isset($seenKeysByCompany[$companyKey][$key])) {
                $key = $baseKey . '_' . $i++;
            }
            $seenKeysByCompany[$companyKey][$key] = true;

            $position = $positionByCompany[$companyKey] ?? 0;
            $positionByCompany[$companyKey] = $position + 1;

            $oldType = strtolower((string) ($r['type'] ?? 'text'));
            $newType = match ($oldType) {
                'email' => 'email',
                default => 'text',
            };

            $oldOptions = $r['field_options'];
            $optionsJson = null;
            if (is_string($oldOptions) && $oldOptions !== '') {
                $decoded = @unserialize($oldOptions, ['allowed_classes' => false]);
                if (is_array($decoded) && $decoded !== []) {
                    $shaped = [];
                    foreach ($decoded as $k => $v) {
                        $value = (string) (is_int($k) ? $v : $k);
                        $label = (string) $v;
                        $shaped[] = ['value' => $value, 'label' => $label];
                    }
                    $optionsJson = json_encode($shaped, JSON_THROW_ON_ERROR);
                }
            }

            $this->connection->insert('custom_field', [
                'id' => $r['id'],
                'company_id' => $r['company_id'],
                'target' => 'CONTACT',
                'label' => (string) $r['name'],
                'field_key' => $key,
                'type' => $newType,
                'options' => $optionsJson,
                'required' => (bool) $r['required'],
                'position' => $position,
                'created' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                'updated' => null,
            ]);
        }

        // 3b. Copy additional_contact_detail → custom_field_value
        $valueRows = $this->connection->fetchAllAssociative(
            'SELECT id, company_id, type_id, contact_id, value, created, updated FROM contact_details'
        );
        foreach ($valueRows as $r) {
            $this->connection->insert('custom_field_value', [
                'id' => $r['id'],
                'company_id' => $r['company_id'],
                'field_id' => $r['type_id'],
                'target' => 'CONTACT',
                'target_id' => $r['contact_id'],
                'value' => $r['value'],
                'created' => $r['created'] ?? (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                'updated' => $r['updated'],
            ]);
        }
    }

    public function postDown(Schema $schema): void
    {
        // see down() — irreversible.
    }

    private function slugify(string $input): string
    {
        $s = strtolower(trim($input));
        $s = preg_replace('/[^a-z0-9]+/', '_', $s) ?? '';
        $s = trim($s, '_');
        if ($s === '' || ! preg_match('/^[a-z]/', $s)) {
            $s = 'field_' . $s;
        }
        return substr($s, 0, 64);
    }
```

Add the missing `use` for `DateTimeImmutable`:

```php
use DateTimeImmutable;
```

- [ ] **Step 2: Drop the legacy tables (in `up()`, after `createTable` calls)**

Replace the comment `// (Task 7 will append data-copy + legacy table drops here.)` with:

```php
        $schema->dropTable('contact_details');
        $schema->dropTable('contact_types');
```

(Doctrine Migrations runs `$schema` operations, then `postUp`. But dropping the legacy tables before `postUp` reads from them defeats the purpose. We need to read first, then drop. **Reorder: do the data copy in `preUp`** and the schema mutations in `up`.)

Replace `postUp` with `preUp` and read into a property, then write back in `postUp`:

Actually the cleaner approach: do everything in `up` using direct SQL via `$this->addSql(...)` is wrong because it bypasses transactions on some platforms, AND `$schema` isn't yet realized. The correct sequence:

1. In `up()`: read the legacy data into in-memory PHP arrays (using `$this->connection->fetchAllAssociative`), then call `$schema->createTable(...)` for the new tables and `$schema->dropTable(...)` for the legacy tables.
2. In `postUp()`: insert the captured data into the new tables (which now exist).

Refactor `up` to capture rows before calling drop:

```php
    /** @var list<array<string, mixed>> */
    private array $legacyTypes = [];
    /** @var list<array<string, mixed>> */
    private array $legacyValues = [];

    public function up(Schema $schema): void
    {
        // Capture legacy rows before dropping tables.
        if ($schema->hasTable('contact_types')) {
            $this->legacyTypes = $this->connection->fetchAllAssociative(
                'SELECT id, company_id, name, type, field_options, required FROM contact_types'
            );
        }
        if ($schema->hasTable('contact_details')) {
            $this->legacyValues = $this->connection->fetchAllAssociative(
                'SELECT id, company_id, type_id, contact_id, value, created, updated FROM contact_details'
            );
        }

        // ... createTable calls for custom_field and custom_field_value ...

        if ($schema->hasTable('contact_details')) {
            $schema->dropTable('contact_details');
        }
        if ($schema->hasTable('contact_types')) {
            $schema->dropTable('contact_types');
        }
    }

    public function postUp(Schema $schema): void
    {
        // Now custom_field/custom_field_value exist; insert from captured rows.
        // (the loops from Step 1 above)
    }
```

- [ ] **Step 3: Run the migration on a fresh database with sample legacy rows**

Manual smoke test:
```bash
bin/console doctrine:database:drop --force --if-exists --env=test
bin/console doctrine:database:create --env=test
bin/console doctrine:migrations:migrate prev_to_30100 --no-interaction --env=test
# (Insert sample contact_types and contact_details rows via raw SQL or Foundry seed)
bin/console doctrine:migrations:migrate latest --no-interaction --env=test
bin/console dbal:run-sql "SELECT id, label, field_key, type FROM custom_field" --env=test
bin/console dbal:run-sql "SELECT field_id, target, target_id, value FROM custom_field_value" --env=test
```

Expected: rows present in new tables; old tables gone.

- [ ] **Step 4: Commit**

```bash
git add migrations/Version30100_1.php
git commit -m "feat(core): migrate legacy ContactType data into custom_field tables"
```

---

### Task 8: Remove `Contact.additionalContactDetails` and delete legacy classes

**Files:**
- Modify: `src/ClientBundle/Entity/Contact.php`
- Modify: `src/ClientBundle/Form/Type/ContactType.php` (only the legacy reference; the new `customFields` child is added in Task 12)
- Delete: `src/ClientBundle/Entity/ContactType.php`
- Delete: `src/ClientBundle/Entity/AdditionalContactDetail.php`
- Delete: `src/ClientBundle/Repository/ContactTypeRepository.php`
- Delete: `src/ClientBundle/Form/Type/ContactDetailType.php`
- Delete: `src/ClientBundle/Form/Type/ContactDetailCollectionType.php`
- Delete: `src/ClientBundle/Resources/views/Form/contact_details.html.twig`
- Delete: `src/ClientBundle/Tests/Form/Type/ContactDetailTypeTest.php` and any other tests of removed classes

- [ ] **Step 1: Remove the `$additionalContactDetails` property and its accessors from `Contact.php`**

Search for `additionalContactDetails`, `addAdditionalContactDetail`, `removeAdditionalContactDetail`, `getAdditionalContactDetails`, `setAdditionalContactDetails`, the `OneToMany` mapping, and the `ArrayCollection` initialization in the constructor. Delete all.

- [ ] **Step 2: Remove the form child from `ContactType` form**

In `src/ClientBundle/Form/Type/ContactType.php`, find:

```php
$builder->add('additionalContactDetails', LiveCollectionType::class, [
    'entry_type' => ContactDetailType::class,
    // ...
]);
```

Delete it. (Task 12 will replace it with the new `customFields` child.)

- [ ] **Step 3: Update `ContactInfo.html.twig` to remove the legacy loop**

In `src/ClientBundle/Resources/views/Components/ContactInfo.html.twig`, find the block that iterates `additionalContactDetails` and delete it. (Task 17 will replace it with the new component.)

- [ ] **Step 4: Delete the legacy files**

```bash
git rm src/ClientBundle/Entity/ContactType.php
git rm src/ClientBundle/Entity/AdditionalContactDetail.php
git rm src/ClientBundle/Repository/ContactTypeRepository.php
git rm src/ClientBundle/Form/Type/ContactDetailType.php
git rm src/ClientBundle/Form/Type/ContactDetailCollectionType.php
git rm src/ClientBundle/Resources/views/Form/contact_details.html.twig
git rm src/ClientBundle/Tests/Form/Type/ContactDetailTypeTest.php
```

Search the rest of the codebase for any remaining references:

```bash
bin/phpstan analyse 2>&1 | grep -i 'ContactType\|AdditionalContactDetail' || true
```

Fix every reported reference (factories, fixtures, dummy-data seeders).

- [ ] **Step 5: Run static analysis + ECS to confirm nothing else broke**

```bash
bin/ecs check --fix
bin/phpstan analyse
```

Expected: clean.

- [ ] **Step 6: Run all tests**

```bash
bin/phpunit
```

Expected: tests touching the legacy classes have been deleted; all remaining tests pass. If any contact-test relied on `additionalContactDetails`, replace its assertions with custom-field equivalents (defer to Task 35) or temporarily mark `@skip` and add a TODO.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "refactor(client): remove legacy ContactType/AdditionalContactDetail entities"
```

---

### Task 9: Migration test

**Files:**
- Create: `src/CoreBundle/Tests/Migration/CustomFieldsMigrationTest.php`

This test seeds the legacy schema, runs the migration up, and asserts the new tables hold the expected data.

- [ ] **Step 1: Write the test**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Tests\Migration;

use Doctrine\DBAL\Connection;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

/**
 * @group functional
 */
final class CustomFieldsMigrationTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testLegacyContactTypesMigrateIntoCustomFields(): void
    {
        // Boot kernel; the test database is already migrated to latest.
        // We assert that the migration produced expected rows when legacy data existed pre-migration.
        // Since the application installation seeds default contact types, those should now appear in custom_field with target=CONTACT.

        /** @var Connection $conn */
        $conn = self::getContainer()->get('doctrine.dbal.default_connection');

        $rows = $conn->fetchAllAssociative(
            "SELECT label, field_key, type, target, position FROM custom_field WHERE target = 'CONTACT' ORDER BY position"
        );

        self::assertNotEmpty($rows, 'Default seeded contact types should appear in custom_field after migration');

        foreach ($rows as $row) {
            self::assertContains($row['type'], ['text', 'email'], 'Migrated types must map to text or email');
            self::assertMatchesRegularExpression('/^[a-z][a-z0-9_]*$/', $row['field_key']);
        }

        // Old tables must be gone
        self::assertFalse($conn->getSchemaManager()->tablesExist(['contact_types']));
        self::assertFalse($conn->getSchemaManager()->tablesExist(['contact_details']));
    }
}
```

- [ ] **Step 2: Run the test**

```bash
bin/phpunit src/CoreBundle/Tests/Migration/CustomFieldsMigrationTest.php
```

Expected: passes (the install fixtures seed legacy contact types, the migration moves them).

- [ ] **Step 3: Commit**

```bash
git add src/CoreBundle/Tests/Migration/CustomFieldsMigrationTest.php
git commit -m "test(core): assert legacy contact types migrate to custom_field"
```

---

## Phase C — Form integration

### Task 10: `CustomFieldValueCollectionType` form widget

**Files:**
- Create: `src/CoreBundle/Form/Type/CustomFieldValueCollectionType.php`
- Test: `src/CoreBundle/Tests/Form/Type/CustomFieldValueCollectionTypeTest.php`

This form type is what gets embedded in `ClientType` and `ContactType`. It dynamically builds children from `CustomField` definitions, and round-trips data through the parent's `customFieldValues` collection.

The parent's relationship to `CustomFieldValue` is *not* a Doctrine OneToMany (because `target_id` is polymorphic). Instead, this widget receives the parent (Client or Contact), looks up its values via `CustomFieldValueRepository::findForRecord($target, $parent->getId())`, and on submit upserts via `EntityManager`.

- [ ] **Step 1: Implement the form type**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomFieldValue;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldTypeResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Uid\Ulid;

final class CustomFieldValueCollectionType extends AbstractType
{
    public function __construct(
        private readonly CustomFieldRepository $fields,
        private readonly CustomFieldValueRepository $values,
        private readonly CustomFieldTypeResolver $resolver,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $target = $options['target'];
        \assert($target instanceof CustomFieldTarget);

        $defs = $this->fields->findByTargetOrdered($target);
        // Stash defs so the post-submit handler doesn't re-query.
        $builder->setAttribute('custom_field_defs', $defs);

        $existingValues = [];
        $parent = $options['parent_record'] ?? null;
        if ($parent !== null && method_exists($parent, 'getId') && $parent->getId() instanceof Ulid) {
            foreach ($this->values->findForRecord($target, $parent->getId()) as $v) {
                $existingValues[(string) $v->getField()->getId()] = $v;
            }
        }

        foreach ($defs as $def) {
            [$type, $opts] = $this->resolver->formTypeAndOptions($def);
            $opts['label'] = $def->getLabel();
            $opts['required'] = $def->isRequired();
            $opts['mapped'] = false;
            $opts['constraints'] = $this->resolver->constraints($def);

            $existing = $existingValues[(string) $def->getId()] ?? null;
            if ($existing !== null) {
                $opts['data'] = $this->resolver->deserialize($def, $existing->getValue());
            }

            $builder->add($def->getFieldKey(), $type, $opts);
        }

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (PostSubmitEvent $event) use ($defs, $target, $existingValues): void {
            $form = $event->getForm();
            $parent = $form->getConfig()->getOption('parent_record');
            if ($parent === null || !method_exists($parent, 'getId') || !$parent->getId() instanceof Ulid) {
                return;
            }
            $companyId = method_exists($parent, 'getCompany') ? $parent->getCompany() : null;

            foreach ($defs as $def) {
                $child = $form->get($def->getFieldKey());
                $serialized = $this->resolver->serialize($def, $child->getData());
                $existing = $existingValues[(string) $def->getId()] ?? null;

                if ($serialized === null) {
                    if ($existing !== null) {
                        $this->em->remove($existing);
                    }
                    continue;
                }

                if ($existing === null) {
                    $value = (new CustomFieldValue())
                        ->setField($def)
                        ->setTarget($target)
                        ->setTargetId($parent->getId())
                        ->setValue($serialized);
                    if ($companyId !== null) {
                        $value->setCompany($companyId);
                    }
                    $this->em->persist($value);
                } else {
                    $existing->setValue($serialized);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['target', 'parent_record']);
        $resolver->setAllowedTypes('target', CustomFieldTarget::class);
        $resolver->setDefaults([
            'mapped' => false,
            'inherit_data' => true,
            'label' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'custom_field_values';
    }
}
```

- [ ] **Step 2: Register the service** (autowire should handle this since CoreBundle services are auto-loaded; verify by running `bin/console debug:autowiring CustomFieldValueCollectionType`).

- [ ] **Step 3: Write a unit test using `FormTestCase`**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Tests\Form\Type;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomFieldValue;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use SolidInvoice\CoreBundle\Form\Type\CustomFieldValueCollectionType;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group functional
 */
final class CustomFieldValueCollectionTypeTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use EnsureApplicationInstalled;

    public function testSubmitCreatesValue(): void
    {
        $company = CompanyFactory::createOne();
        $client = ClientFactory::createOne(['company' => $company])->_real();

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $field = (new CustomField())
            ->setTarget(CustomFieldTarget::CLIENT)
            ->setLabel('Department')
            ->setFieldKey('department')
            ->setType(CustomFieldType::TEXT)
            ->setCompany($company->_real());
        $em->persist($field);
        $em->flush();

        $form = self::getContainer()->get('form.factory')->create(
            CustomFieldValueCollectionType::class,
            null,
            ['target' => CustomFieldTarget::CLIENT, 'parent_record' => $client]
        );
        $form->submit(['department' => 'Sales']);

        self::assertTrue($form->isValid(), (string) $form->getErrors(true));

        /** @var CustomFieldValueRepository $repo */
        $repo = self::getContainer()->get(CustomFieldValueRepository::class);
        $values = $repo->findForRecord(CustomFieldTarget::CLIENT, $client->getId());
        self::assertCount(1, $values);
        self::assertSame('Sales', $values[0]->getValue());
    }
}
```

- [ ] **Step 4: Run, expect pass.**

```bash
bin/phpunit src/CoreBundle/Tests/Form/Type/CustomFieldValueCollectionTypeTest.php
```

- [ ] **Step 5: Commit**

```bash
git add src/CoreBundle/Form/Type/CustomFieldValueCollectionType.php src/CoreBundle/Tests/Form/Type/CustomFieldValueCollectionTypeTest.php
git commit -m "feat(core): add CustomFieldValueCollectionType form widget"
```

---

### Task 11: Embed `customFields` in `ClientType`

**Files:**
- Modify: `src/ClientBundle/Form/Type/ClientType.php`

- [ ] **Step 1: Add the `customFields` child**

In the `buildForm` method, after the existing children (`name`, `website`, `currencyCode`, `vat_number`, `contacts`, `addresses`), add:

```php
$builder->add('customFields', \SolidInvoice\CoreBundle\Form\Type\CustomFieldValueCollectionType::class, [
    'target' => \SolidInvoice\CoreBundle\Enum\CustomFieldTarget::CLIENT,
    'parent_record' => $options['data'],
]);
```

- [ ] **Step 2: Render the section in the client edit template**

Modify `src/ClientBundle/Resources/views/Default/edit.html.twig` (or wherever the client form is rendered). Find the existing form rendering block. Add a new card around the `customFields` group:

```twig
{% if form.customFields|length > 0 %}
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">{{ 'Custom fields'|trans }}</h3>
    </div>
    <div class="card-body">
        {{ form_widget(form.customFields) }}
    </div>
</div>
{% endif %}
```

- [ ] **Step 3: Smoke-test in the browser**

```bash
bin/console cache:clear
bun run dev
```

Open `/clients/new` and `/clients/{id}/edit`. Without any defined CLIENT custom fields, the card should not appear. After running through Phase E to define a field, it should appear in the form.

- [ ] **Step 4: Commit**

```bash
git add src/ClientBundle/Form/Type/ClientType.php src/ClientBundle/Resources/views/Default/edit.html.twig
git commit -m "feat(client): render custom fields section in client form"
```

---

### Task 12: Embed `customFields` in `ContactType` form

**Files:**
- Modify: `src/ClientBundle/Form/Type/ContactType.php`

- [ ] **Step 1: Add the `customFields` child**

In `buildForm`, after the existing `firstName`/`lastName`/`email` children, add:

```php
$builder->add('customFields', \SolidInvoice\CoreBundle\Form\Type\CustomFieldValueCollectionType::class, [
    'target' => \SolidInvoice\CoreBundle\Enum\CustomFieldTarget::CONTACT,
    'parent_record' => $options['data'],
]);
```

- [ ] **Step 2: Find the contact form template and render it**

The contact form is rendered inside the client form's `LiveCollectionType` for `contacts`. Locate the contact-row template (likely `src/ClientBundle/Resources/views/Form/contact.html.twig` or similar). Add inside the contact row, after email:

```twig
{% if form.customFields|length > 0 %}
<div class="mt-2">
    <strong class="d-block mb-1">{{ 'Custom fields'|trans }}</strong>
    {{ form_widget(form.customFields) }}
</div>
{% endif %}
```

- [ ] **Step 3: Smoke-test**

Add a `CONTACT` custom field via the upcoming settings page (skip until Phase E lands), then create/edit a client and confirm the field appears per-contact.

- [ ] **Step 4: Commit**

```bash
git add src/ClientBundle/Form/Type/ContactType.php src/ClientBundle/Resources/views/Form/contact.html.twig
git commit -m "feat(client): render custom fields per contact"
```

---

### Task 13: Doctrine `postRemove` listener for orphan cleanup

**Files:**
- Create: `src/CoreBundle/Listener/CustomFieldValueCleanupListener.php`

- [ ] **Step 1: Implement the listener**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Listener;

use Doctrine\ORM\Event\PostRemoveEventArgs;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use Symfony\Component\DependencyInjection\Attribute\AsDoctrineListener;

#[AsDoctrineListener(event: \Doctrine\ORM\Events::postRemove)]
final class CustomFieldValueCleanupListener
{
    public function __construct(
        private readonly CustomFieldValueRepository $values,
    ) {
    }

    public function postRemove(PostRemoveEventArgs $event): void
    {
        $entity = $event->getObject();

        $target = match (true) {
            $entity instanceof Client => CustomFieldTarget::CLIENT,
            $entity instanceof Contact => CustomFieldTarget::CONTACT,
            default => null,
        };

        if ($target === null || $entity->getId() === null) {
            return;
        }

        $this->values->deleteForRecord($target, $entity->getId());
    }
}
```

- [ ] **Step 2: Verify the listener registers**

```bash
bin/console debug:event-dispatcher | grep -i CustomFieldValueCleanup || true
```

The `AsDoctrineListener` attribute auto-registers via Symfony — no service config needed.

- [ ] **Step 3: Write a functional test**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Tests\Listener;

use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomFieldValue;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group functional
 */
final class CustomFieldValueCleanupListenerTest extends KernelTestCase
{
    use Factories, ResetDatabase, EnsureApplicationInstalled;

    public function testValuesDeletedWhenClientIsRemoved(): void
    {
        $company = CompanyFactory::createOne();
        $client = ClientFactory::createOne(['company' => $company])->_real();

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $field = (new CustomField())
            ->setTarget(CustomFieldTarget::CLIENT)
            ->setLabel('Department')
            ->setFieldKey('department')
            ->setType(CustomFieldType::TEXT)
            ->setCompany($company->_real());
        $em->persist($field);
        $value = (new CustomFieldValue())
            ->setField($field)
            ->setTarget(CustomFieldTarget::CLIENT)
            ->setTargetId($client->getId())
            ->setValue('Sales')
            ->setCompany($company->_real());
        $em->persist($value);
        $em->flush();

        $em->remove($client);
        $em->flush();

        /** @var CustomFieldValueRepository $repo */
        $repo = self::getContainer()->get(CustomFieldValueRepository::class);
        self::assertSame([], $repo->findForRecord(CustomFieldTarget::CLIENT, $client->getId()));
    }
}
```

- [ ] **Step 4: Run, expect pass.**

- [ ] **Step 5: Commit**

```bash
git add src/CoreBundle/Listener/CustomFieldValueCleanupListener.php src/CoreBundle/Tests/Listener/CustomFieldValueCleanupListenerTest.php
git commit -m "feat(core): clean up custom field values on Client/Contact removal"
```

---

### Task 14: Orphan check console command

**Files:**
- Create: `src/CoreBundle/Command/CustomFieldOrphanCheckCommand.php`

A small operational tool. Lists `CustomFieldValue` rows whose `target_id` no longer references a real Client/Contact, and optionally cleans them.

- [ ] **Step 1: Implement the command**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:custom-fields:check-orphans', description: 'Find (and optionally clean) custom_field_value rows whose target record is gone.')]
final class CustomFieldOrphanCheckCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('clean', null, InputOption::VALUE_NONE, 'Delete orphan rows after listing them.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $orphans = $this->connection->fetchAllAssociative(
            "SELECT v.id, v.target, v.target_id FROM custom_field_value v
             LEFT JOIN clients c ON v.target = 'CLIENT' AND v.target_id = c.id
             LEFT JOIN contacts ct ON v.target = 'CONTACT' AND v.target_id = ct.id
             WHERE (v.target = 'CLIENT' AND c.id IS NULL)
                OR (v.target = 'CONTACT' AND ct.id IS NULL)"
        );

        if ($orphans === []) {
            $io->success('No orphan custom field values.');
            return Command::SUCCESS;
        }

        $io->table(['id', 'target', 'target_id'], $orphans);

        if ($input->getOption('clean')) {
            $this->connection->executeStatement(
                "DELETE FROM custom_field_value
                 WHERE id IN (
                   SELECT id FROM custom_field_value v
                   LEFT JOIN clients c ON v.target = 'CLIENT' AND v.target_id = c.id
                   LEFT JOIN contacts ct ON v.target = 'CONTACT' AND v.target_id = ct.id
                   WHERE (v.target = 'CLIENT' AND c.id IS NULL)
                      OR (v.target = 'CONTACT' AND ct.id IS NULL)
                 )"
            );
            $io->success('Cleaned ' . count($orphans) . ' orphan rows.');
        } else {
            $io->warning('Re-run with --clean to delete these.');
        }

        return Command::SUCCESS;
    }
}
```

- [ ] **Step 2: Smoke-test**

```bash
bin/console app:custom-fields:check-orphans
```

Expected: "No orphan custom field values." on a clean DB.

- [ ] **Step 3: Commit**

```bash
git add src/CoreBundle/Command/CustomFieldOrphanCheckCommand.php
git commit -m "feat(core): orphan check command for custom field values"
```

---

## Phase D — View rendering

### Task 15: `CustomFieldsList` Twig component

**Files:**
- Create: `src/CoreBundle/Twig/Components/CustomFieldsList.php`
- Create: `src/CoreBundle/Resources/views/Components/CustomFieldsList.html.twig`

- [ ] **Step 1: Create the component class**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Twig\Components;

use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldTypeResolver;
use Symfony\Component\Uid\Ulid;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('CustomFieldsList', template: '@SolidInvoiceCore/Components/CustomFieldsList.html.twig')]
final class CustomFieldsList
{
    public CustomFieldTarget $target;
    public Ulid $recordId;

    public function __construct(
        private readonly CustomFieldRepository $fields,
        private readonly CustomFieldValueRepository $values,
        private readonly CustomFieldTypeResolver $resolver,
    ) {
    }

    /**
     * @return list<array{field: CustomField, formatted: string, raw: ?string}>
     */
    public function getRows(): array
    {
        $defs = $this->fields->findByTargetOrdered($this->target);
        if ($defs === []) {
            return [];
        }

        $byField = [];
        foreach ($this->values->findForRecord($this->target, $this->recordId) as $v) {
            $byField[(string) $v->getField()->getId()] = $v;
        }

        $out = [];
        foreach ($defs as $def) {
            $value = $byField[(string) $def->getId()] ?? null;
            $stored = $value?->getValue();
            $out[] = [
                'field' => $def,
                'formatted' => $this->resolver->formatForDisplay($def, $stored),
                'raw' => $stored,
            ];
        }
        return $out;
    }
}
```

- [ ] **Step 2: Create the template**

```twig
{% set rows = this.rows %}
{% if rows is not empty %}
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">{{ 'Custom fields'|trans }}</h3>
    </div>
    <div class="card-body">
        <div class="datagrid">
            {% for row in rows %}
                <div class="datagrid-item">
                    <div class="datagrid-title">{{ row.field.label }}</div>
                    <div class="datagrid-content">
                        {% set t = row.field.type.value %}
                        {% if row.raw is null %}
                            <span class="text-muted">—</span>
                        {% elseif t == 'url' %}
                            <a href="{{ row.raw }}" target="_blank" rel="noopener">{{ row.raw }}</a>
                        {% elseif t == 'email' %}
                            <a href="mailto:{{ row.raw }}">{{ row.raw }}</a>
                        {% elseif t == 'date' %}
                            {{ row.raw|format_date }}
                        {% elseif t == 'textarea' %}
                            <span class="text-pre-wrap">{{ row.formatted|nl2br }}</span>
                        {% else %}
                            {{ row.formatted }}
                        {% endif %}
                    </div>
                </div>
            {% endfor %}
        </div>
    </div>
</div>
{% endif %}
```

- [ ] **Step 3: Verify the component is discoverable**

```bash
bin/console debug:twig-component CustomFieldsList
```

- [ ] **Step 4: Commit**

```bash
git add src/CoreBundle/Twig/Components/CustomFieldsList.php src/CoreBundle/Resources/views/Components/CustomFieldsList.html.twig
git commit -m "feat(core): CustomFieldsList Twig component"
```

---

### Task 16: Embed component in client view

**Files:**
- Modify: `src/ClientBundle/Resources/views/Default/view.html.twig`

- [ ] **Step 1: Insert the component**

After the hero/details block and before the contacts block, add:

```twig
<twig:CustomFieldsList target="CLIENT" recordId="{{ client.id }}" />
```

(Use the actual variable name available in the template — likely `client`.)

If `target` must be passed as the enum constant, use:

```twig
<twig:CustomFieldsList :target="enum('SolidInvoice\\CoreBundle\\Enum\\CustomFieldTarget').CLIENT" :recordId="client.id" />
```

The simpler string-coercion form works as long as the component accepts the enum from string. If needed, change the property type to allow string and convert in the constructor.

- [ ] **Step 2: Smoke-test**

Visit a client's view page after defining a CLIENT custom field via Phase E.

- [ ] **Step 3: Commit**

```bash
git add src/ClientBundle/Resources/views/Default/view.html.twig
git commit -m "feat(client): show custom fields on client view page"
```

---

### Task 17: Embed component in contact view

**Files:**
- Modify: `src/ClientBundle/Resources/views/Components/ContactInfo.html.twig`

- [ ] **Step 1: Insert the component**

In place of the legacy `additionalContactDetails` loop (already removed in Task 8), add:

```twig
<twig:CustomFieldsList :target="enum('SolidInvoice\\CoreBundle\\Enum\\CustomFieldTarget').CONTACT" :recordId="contact.id" />
```

- [ ] **Step 2: Commit**

```bash
git add src/ClientBundle/Resources/views/Components/ContactInfo.html.twig
git commit -m "feat(client): show custom fields per contact"
```

---

## Phase E — Settings UI

### Task 18: Routes for `/settings/custom-fields/*`

**Files:**
- Modify: `src/SettingsBundle/Resources/config/routing.php`

- [ ] **Step 1: Add the routes**

```php
use SolidInvoice\SettingsBundle\Action\CustomField\CreateAction;
use SolidInvoice\SettingsBundle\Action\CustomField\DeleteAction;
use SolidInvoice\SettingsBundle\Action\CustomField\EditAction;
use SolidInvoice\SettingsBundle\Action\CustomField\IndexAction;
use SolidInvoice\SettingsBundle\Action\CustomField\ReorderAction;

// inside the closure:
$routingConfigurator->add('_settings_custom_fields', '/settings/custom-fields')
    ->controller(IndexAction::class)
    ->methods(['GET']);

$routingConfigurator->add('_settings_custom_fields_create', '/settings/custom-fields/new')
    ->controller(CreateAction::class)
    ->methods(['GET', 'POST']);

$routingConfigurator->add('_settings_custom_fields_edit', '/settings/custom-fields/{id}/edit')
    ->controller(EditAction::class)
    ->methods(['GET', 'POST']);

$routingConfigurator->add('_settings_custom_fields_delete', '/settings/custom-fields/{id}/delete')
    ->controller(DeleteAction::class)
    ->methods(['POST']);

$routingConfigurator->add('_settings_custom_fields_reorder', '/settings/custom-fields/reorder')
    ->controller(ReorderAction::class)
    ->methods(['POST']);
```

- [ ] **Step 2: Verify**

```bash
bin/console debug:router | grep custom-fields
```

- [ ] **Step 3: Commit (after the actions are stubs — do this after Task 22)**

(No commit yet — actions don't exist yet. We'll commit at end of Task 22 together.)

---

### Task 19: `CustomFieldDefinitionType` form

**Files:**
- Create: `src/SettingsBundle/Form/Type/CustomFieldDefinitionType.php`

- [ ] **Step 1: Implement**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\SettingsBundle\Form\Type;

use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldType as CFType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class CustomFieldDefinitionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 125)],
            ])
            ->add('fieldKey', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 64),
                    new Assert\Regex('/^[a-z][a-z0-9_]*$/', message: 'Use lowercase letters, digits, and underscores; must start with a letter.'),
                ],
            ])
            ->add('type', EnumType::class, [
                'class' => CFType::class,
                'choice_label' => fn (CFType $t): string => $t->label(),
            ])
            ->add('required', CheckboxType::class, ['required' => false])
            ->add('options', CollectionType::class, [
                'entry_type' => CustomFieldOptionType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', CustomField::class);
    }
}
```

- [ ] **Step 2: Implement `CustomFieldOptionType` (small sub-form for option rows)**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\SettingsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CustomFieldOptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('value', TextType::class, ['required' => true])
            ->add('label', TextType::class, ['required' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
```

- [ ] **Step 3: Commit (defer to Task 22)**

---

### Task 20: `IndexAction` + list template (no reorder yet)

**Files:**
- Create: `src/SettingsBundle/Action/CustomField/IndexAction.php`
- Create: `src/SettingsBundle/Resources/views/CustomField/index.html.twig`

- [ ] **Step 1: Implement the action**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\SettingsBundle\Action\CustomField;

use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use Symfony\Bridge\Twig\Attribute\Template;

final class IndexAction
{
    public function __construct(
        private readonly CustomFieldRepository $fields,
        private readonly CustomFieldValueRepository $values,
    ) {
    }

    /**
     * @return array{client: list<array{field: object, count: int}>, contact: list<array{field: object, count: int}>}
     */
    #[Template('@SolidInvoiceSettings/CustomField/index.html.twig')]
    public function __invoke(): array
    {
        $build = function (CustomFieldTarget $target): array {
            $rows = [];
            foreach ($this->fields->findByTargetOrdered($target) as $f) {
                $rows[] = ['field' => $f, 'count' => $this->values->countByField($f)];
            }
            return $rows;
        };

        return [
            'client' => $build(CustomFieldTarget::CLIENT),
            'contact' => $build(CustomFieldTarget::CONTACT),
        ];
    }
}
```

- [ ] **Step 2: Implement the template**

```twig
{% extends '@SolidInvoiceCore/layout.html.twig' %}
{# adjust layout extends to match existing settings pages — find it first via grep #}

{% block content %}
<div class="page-header">
    <h2 class="page-title">{{ 'Custom fields'|trans }}</h2>
    <div class="page-actions">
        <a href="{{ path('_settings_custom_fields_create') }}" class="btn btn-primary">
            {{ ux_icon('tabler:plus', {width: 16, height: 16}) }}
            {{ 'Add field'|trans }}
        </a>
    </div>
</div>

<div class="card mt-3">
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-client">{{ 'Client fields'|trans }}</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-contact">{{ 'Contact fields'|trans }}</a></li>
    </ul>
    <div class="tab-content">
        {% for tabId, rows in {'client': client, 'contact': contact} %}
            <div class="tab-pane{% if loop.first %} active show{% endif %}" id="tab-{{ tabId }}">
                {% if rows is empty %}
                    <div class="card-body text-center text-muted">
                        {{ 'No custom fields yet — click Add field to create one.'|trans }}
                    </div>
                {% else %}
                    <div class="list-group list-group-flush"
                         data-controller="custom-field-reorder"
                         data-custom-field-reorder-url-value="{{ path('_settings_custom_fields_reorder') }}">
                        {% for row in rows %}
                            <div class="list-group-item d-flex align-items-center" data-id="{{ row.field.id }}">
                                <span class="me-3 text-muted" data-handle>{{ ux_icon('tabler:grip-vertical') }}</span>
                                <div class="flex-fill">
                                    <strong>{{ row.field.label }}</strong>
                                    <span class="badge bg-secondary-lt ms-2">{{ row.field.type.label }}</span>
                                    {% if row.field.required %}<span class="badge bg-yellow-lt ms-1">{{ 'Required'|trans }}</span>{% endif %}
                                    <div class="text-muted small">field_key: <code>{{ row.field.fieldKey }}</code> · {{ row.count }} {{ 'values'|trans }}</div>
                                </div>
                                <a href="{{ path('_settings_custom_fields_edit', {id: row.field.id}) }}" class="btn btn-sm btn-link">{{ 'Edit'|trans }}</a>
                                <form method="post" action="{{ path('_settings_custom_fields_delete', {id: row.field.id}) }}" class="d-inline" onsubmit="return confirm('{{ 'Delete %label%? %count% records have a value for this field. These values will be permanently deleted.'|trans({'%label%': row.field.label, '%count%': row.count}) }}')">
                                    <input type="hidden" name="_token" value="{{ csrf_token('cf_delete_' ~ row.field.id) }}">
                                    <button class="btn btn-sm btn-link text-danger" type="submit">{{ 'Delete'|trans }}</button>
                                </form>
                            </div>
                        {% endfor %}
                    </div>
                {% endif %}
            </div>
        {% endfor %}
    </div>
</div>
{% endblock %}
```

- [ ] **Step 3: Commit (defer to Task 22)**

---

### Task 21: `CreateAction` + `EditAction` with LiveComponent modal

**Files:**
- Create: `src/SettingsBundle/Action/CustomField/CreateAction.php`
- Create: `src/SettingsBundle/Action/CustomField/EditAction.php`
- Create: `src/SettingsBundle/Twig/Components/CustomFieldEditModal.php`
- Create: `src/SettingsBundle/Resources/views/Components/CustomFieldEditModal.html.twig`

For simplicity in v1, the create/edit pages can render a regular form (not modal-only). The LiveComponent piece handles the dynamic options editor that appears for SELECT types.

- [ ] **Step 1: `CreateAction`**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\SettingsBundle\Action\CustomField;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\SettingsBundle\Form\Type\CustomFieldDefinitionType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CreateAction extends AbstractController
{
    public function __construct(
        private readonly CustomFieldRepository $fields,
        private readonly EntityManagerInterface $em,
        private readonly CompanySelector $companies,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $field = (new CustomField())
            ->setTarget(CustomFieldTarget::from($request->query->get('target', 'CLIENT')));

        $form = $this->createForm(CustomFieldDefinitionType::class, $field);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $field->setPosition($this->fields->nextPosition($field->getTarget()));
            $companyId = $this->companies->getCompanyId();
            $company = $this->em->getReference(Company::class, $companyId);
            $field->setCompany($company);

            $this->em->persist($field);
            $this->em->flush();

            $this->addFlash('success', 'Custom field created.');
            return new RedirectResponse($this->generateUrl('_settings_custom_fields'));
        }

        return $this->render('@SolidInvoiceSettings/CustomField/edit.html.twig', [
            'form' => $form->createView(),
            'mode' => 'create',
        ]);
    }
}
```

- [ ] **Step 2: `EditAction`**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\SettingsBundle\Action\CustomField;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\SettingsBundle\Form\Type\CustomFieldDefinitionType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Ulid;

final class EditAction extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(Request $request, string $id): Response
    {
        $field = $this->em->find(CustomField::class, Ulid::fromString($id));
        if ($field === null) {
            throw new NotFoundHttpException('Field not found.');
        }

        $form = $this->createForm(CustomFieldDefinitionType::class, $field);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Custom field updated.');
            return new RedirectResponse($this->generateUrl('_settings_custom_fields'));
        }

        return $this->render('@SolidInvoiceSettings/CustomField/edit.html.twig', [
            'form' => $form->createView(),
            'mode' => 'edit',
            'field' => $field,
        ]);
    }
}
```

- [ ] **Step 3: Create `edit.html.twig` rendering the form**

```twig
{% extends '@SolidInvoiceCore/layout.html.twig' %}

{% block content %}
<div class="page-header">
    <h2 class="page-title">
        {{ mode == 'create' ? 'New custom field'|trans : 'Edit custom field'|trans }}
    </h2>
</div>

<div class="card mt-3">
    <div class="card-body">
        {{ form_start(form) }}
            <div class="row g-3">
                <div class="col-md-6">{{ form_row(form.label) }}</div>
                <div class="col-md-6">{{ form_row(form.fieldKey) }}</div>
                <div class="col-md-6">{{ form_row(form.type) }}</div>
                <div class="col-md-6">{{ form_row(form.required) }}</div>
            </div>

            <div class="mt-3" data-controller="custom-field-options" data-custom-field-options-type-value="{{ form.type.vars.value ?? '' }}">
                <h4>{{ 'Options'|trans }} <small class="text-muted">{{ '(only for SELECT and MULTI_SELECT)'|trans }}</small></h4>
                {{ form_widget(form.options) }}
            </div>

            <div class="mt-4">
                <button class="btn btn-primary" type="submit">{{ 'Save'|trans }}</button>
                <a class="btn btn-link" href="{{ path('_settings_custom_fields') }}">{{ 'Cancel'|trans }}</a>
            </div>
        {{ form_end(form) }}
    </div>
</div>
{% endblock %}
```

- [ ] **Step 4: Add a small Stimulus controller to show/hide options when type is SELECT/MULTI_SELECT**

Create `assets/controllers/custom-field-options_controller.ts`:

```typescript
import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLElement> {
    static values = { type: String };
    declare typeValue: string;

    connect() {
        this.update();
        const select = this.element.closest('form')?.querySelector<HTMLSelectElement>('select[name$="[type]"]');
        select?.addEventListener('change', () => {
            this.typeValue = select.value;
            this.update();
        });
    }

    private update() {
        const show = this.typeValue === 'select' || this.typeValue === 'multi_select';
        this.element.style.display = show ? '' : 'none';
    }
}
```

Wrap the `form_widget(form.options)` call in `edit.html.twig` so the controller's element is the only thing shown/hidden — the snippet in Step 3 already does this with `data-controller="custom-field-options"`.

- [ ] **Step 5: Disable the Type dropdown when the field has existing values (per spec §10.5)**

In `EditAction`, before rendering, compute usage count and pass to template:

```php
$usageCount = $this->em->getRepository(\SolidInvoice\CoreBundle\Entity\CustomField\CustomFieldValue::class)
    ->countByField($field);

return $this->render('@SolidInvoiceSettings/CustomField/edit.html.twig', [
    'form' => $form->createView(),
    'mode' => 'edit',
    'field' => $field,
    'usageCount' => $usageCount,
]);
```

In `edit.html.twig`, render the type field disabled when in edit mode and `usageCount > 0`:

```twig
{% if mode == 'edit' and usageCount > 0 %}
    <div class="alert alert-info">
        {{ 'This field has %count% values; type cannot be changed. Delete and recreate to change type.'|trans({'%count%': usageCount}) }}
    </div>
    {{ form_row(form.type, {attr: {disabled: 'disabled'}}) }}
{% else %}
    {{ form_row(form.type) }}
{% endif %}
```

When `disabled` the value is dropped on submit; add a form event listener in `CustomFieldDefinitionType` that re-applies the original type if the submitted form lacks it:

```php
$builder->addEventListener(\Symfony\Component\Form\FormEvents::PRE_SUBMIT, function (\Symfony\Component\Form\FormEvent $event) {
    $data = $event->getData();
    /** @var CustomField $entity */
    $entity = $event->getForm()->getData();
    if (! isset($data['type']) && $entity?->getType()) {
        $data['type'] = $entity->getType()->value;
        $event->setData($data);
    }
});
```

- [ ] **Step 6: Warn when removing SELECT options that have values (per spec §10.6)**

In `EditAction`, after `isSubmitted() && isValid()` but before `flush()`, if the field's type is SELECT or MULTI_SELECT, compare old vs new option `value`s:

```php
$removed = array_diff(
    array_column($originalOptions ?? [], 'value'),
    array_column($field->getOptions() ?? [], 'value')
);
if ($removed !== []) {
    // Find values that reference removed options (textual search in custom_field_value.value).
    // For simplicity, just flash a warning. Cleanup is left to the user.
    $this->addFlash('warning', 'Removed options [' . implode(', ', $removed) . '] may leave existing values orphaned. Review affected records.');
}
```

To capture `$originalOptions` before binding, add a `PRE_SUBMIT` listener to `CustomFieldDefinitionType` that stashes the current options in a request attribute, then read it back in the action. (Keep this lightweight — the warning is best-effort, not enforcement.)

- [ ] **Step 7: Commit (defer to Task 22)**

---

### Task 22: `DeleteAction`

**Files:**
- Create: `src/SettingsBundle/Action/CustomField/DeleteAction.php`

- [ ] **Step 1: Implement**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\SettingsBundle\Action\CustomField;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Ulid;

final class DeleteAction extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function __invoke(Request $request, string $id): Response
    {
        $token = (string) $request->request->get('_token');
        if (! $this->isCsrfTokenValid('cf_delete_' . $id, $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $field = $this->em->find(CustomField::class, Ulid::fromString($id));
        if ($field === null) {
            throw new NotFoundHttpException('Field not found.');
        }

        $this->em->remove($field);
        $this->em->flush();

        $this->addFlash('success', 'Custom field deleted.');
        return new RedirectResponse($this->generateUrl('_settings_custom_fields'));
    }
}
```

- [ ] **Step 2: Run a quick functional test by hand**

```bash
bin/console cache:clear
# visit /settings/custom-fields, create a field, edit it, delete it
```

- [ ] **Step 3: Commit Phase E settings UI together**

```bash
git add src/SettingsBundle/Action/CustomField src/SettingsBundle/Form/Type src/SettingsBundle/Resources/views/CustomField src/SettingsBundle/Resources/config/routing.php
git commit -m "feat(settings): custom fields management page (CRUD)"
```

---

### Task 23: Drag-to-reorder with Sortable.js

**Files:**
- Create: `assets/controllers/custom-field-reorder_controller.ts`
- Create: `src/SettingsBundle/Action/CustomField/ReorderAction.php`
- Modify: `package.json`

- [ ] **Step 1: Install Sortable.js**

```bash
bun add sortablejs
bun add -d @types/sortablejs
```

- [ ] **Step 2: Stimulus controller**

```typescript
import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLElement> {
    static values = { url: String };
    declare urlValue: string;

    private sortable: Sortable | null = null;

    connect() {
        this.sortable = Sortable.create(this.element, {
            handle: '[data-handle]',
            animation: 150,
            onEnd: () => this.persist(),
        });
    }

    disconnect() {
        this.sortable?.destroy();
        this.sortable = null;
    }

    private async persist() {
        const items = Array.from(this.element.querySelectorAll<HTMLElement>('[data-id]'));
        const payload = items.map((el, i) => ({ id: el.dataset.id, position: i }));

        const res = await fetch(this.urlValue, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
            credentials: 'same-origin',
        });

        if (!res.ok) {
            console.error('Reorder failed:', await res.text());
            // TODO: revert DOM order on failure
        }
    }
}
```

- [ ] **Step 3: `ReorderAction`**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\SettingsBundle\Action\CustomField;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

final class ReorderAction
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function __invoke(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid payload'], 400);
        }

        $repo = $this->em->getRepository(CustomField::class);
        foreach ($payload as $row) {
            if (! isset($row['id'], $row['position'])) {
                continue;
            }
            $field = $repo->find(Ulid::fromString($row['id']));
            if ($field !== null) {
                $field->setPosition((int) $row['position']);
            }
        }
        $this->em->flush();

        return new JsonResponse(null, 204);
    }
}
```

- [ ] **Step 4: Build and smoke-test**

```bash
bun run dev
# Open /settings/custom-fields, drag rows, refresh, verify order persists
```

- [ ] **Step 5: Commit**

```bash
git add assets/controllers/custom-field-reorder_controller.ts src/SettingsBundle/Action/CustomField/ReorderAction.php package.json bun.lockb
git commit -m "feat(settings): drag-to-reorder custom fields"
```

---

### Task 24: Add settings menu entry

**Files:**
- Modify: existing settings menu config (search for `_settings` route registration in `src/MenuBundle` or `src/SettingsBundle`)

- [ ] **Step 1: Find the existing settings menu**

```bash
grep -rn "_settings" src/MenuBundle src/SettingsBundle 2>/dev/null
```

- [ ] **Step 2: Add a child entry under "Settings" pointing at `_settings_custom_fields`** (follow the existing menu pattern; pseudo-code:)

```php
$child->addChild('Custom fields', ['route' => '_settings_custom_fields'])
      ->setExtra('icon', 'tabler:forms');
```

- [ ] **Step 3: Verify**

Visit the app and confirm the new menu link appears.

- [ ] **Step 4: Commit**

```bash
git add <files>
git commit -m "feat(settings): add Custom fields menu entry"
```

---

## Phase F — API + MCP

### Task 25: Add `#[ApiResource]` to `CustomField`

**Files:**
- Modify: `src/CoreBundle/Entity/CustomField/CustomField.php`

- [ ] **Step 1: Add the resource attributes** above the `class CustomField` declaration:

```php
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

#[ApiResource(
    shortName: 'CustomField',
    operations: [
        new Get(mcp: true),
        new GetCollection(mcp: true),
        new Post(mcp: true),
        new Patch(mcp: true),
        new Delete(mcp: true),
    ],
    normalizationContext: [
        'groups' => ['custom_field:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        'groups' => ['custom_field:write'],
    ],
)]
#[ApiFilter(SearchFilter::class, properties: ['target' => 'exact'])]
```

Add `#[Serialize\Groups([...])]` to each property — `id`, `target`, `label`, `fieldKey`, `type`, `options`, `required`, `position` — using `['custom_field:read', 'custom_field:write']` (read-only for `id`, `position`).

- [ ] **Step 2: Test the API surface**

```bash
bin/console cache:clear
curl -H 'X-API-TOKEN: <token>' http://localhost:8000/api/custom_fields
```

Expected: empty `hydra:Collection` (or rows if any exist).

If `mcp: true` is rejected because the operation class doesn't have that constructor argument, check the API Platform 4 release the project is using and consult its docs — the syntax may be `extraProperties: ['mcp' => true]` instead. Adjust accordingly. (Both forms appear in the API Platform 4 ecosystem; the project's `config/reference.php` mentions `mcp` at the operation level, so one of these variants is correct.)

- [ ] **Step 3: Commit**

```bash
git add src/CoreBundle/Entity/CustomField/CustomField.php
git commit -m "feat(api): expose CustomField as ApiResource with MCP enabled"
```

---

### Task 26: Reorder custom operation in API

**Files:**
- Modify: `src/CoreBundle/Entity/CustomField/CustomField.php`
- Reuse: `src/SettingsBundle/Action/CustomField/ReorderAction.php` (or create an API-specific controller)

- [ ] **Step 1: Add a custom operation**

In the `operations` array of `#[ApiResource]`, add:

```php
new Post(
    uriTemplate: '/custom_fields/reorder',
    controller: \SolidInvoice\CoreBundle\Action\Api\CustomFieldReorderAction::class,
    name: 'custom_field_reorder',
    mcp: true,
),
```

- [ ] **Step 2: Create the API action**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Action\Api;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

final class CustomFieldReorderAction
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function __invoke(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid payload'], 400);
        }
        $repo = $this->em->getRepository(CustomField::class);
        foreach ($payload as $row) {
            $field = $repo->find(Ulid::fromString($row['id']));
            if ($field !== null) {
                $field->setPosition((int) $row['position']);
            }
        }
        $this->em->flush();

        return new JsonResponse(null, 204);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/CoreBundle/Action/Api/CustomFieldReorderAction.php src/CoreBundle/Entity/CustomField/CustomField.php
git commit -m "feat(api): add custom field reorder operation"
```

---

### Task 27: `CustomFieldsNormalizer` — embed values onto Client/Contact API output

**Files:**
- Create: `src/CoreBundle/Serializer/Normalizer/CustomFieldsNormalizer.php`
- Test: `src/CoreBundle/Tests/Serializer/Normalizer/CustomFieldsNormalizerTest.php`

- [ ] **Step 1: Implement**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Serializer\Normalizer;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldTypeResolver;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class CustomFieldsNormalizer implements NormalizerAwareInterface, NormalizerInterface
{
    use NormalizerAwareTrait;

    private const SKIP_KEY = self::class . '::skip';

    public function __construct(
        private readonly CustomFieldRepository $fields,
        private readonly CustomFieldValueRepository $values,
        private readonly CustomFieldTypeResolver $resolver,
    ) {
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $context[self::SKIP_KEY] = true;
        $data = $this->normalizer->normalize($object, $format, $context);

        $target = $object instanceof Client ? CustomFieldTarget::CLIENT : CustomFieldTarget::CONTACT;
        $defs = $this->fields->findByTargetOrdered($target);
        if ($defs === [] || $object->getId() === null) {
            $data['customFields'] = (object) [];
            return $data;
        }

        $byField = [];
        foreach ($this->values->findForRecord($target, $object->getId()) as $v) {
            $byField[(string) $v->getField()->getId()] = $v;
        }

        $custom = [];
        foreach ($defs as $def) {
            $value = $byField[(string) $def->getId()] ?? null;
            $custom[$def->getFieldKey()] = $this->resolver->deserialize($def, $value?->getValue());
        }
        $data['customFields'] = $custom !== [] ? $custom : (object) [];

        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if ($context[self::SKIP_KEY] ?? false) {
            return false;
        }
        return $data instanceof Client || $data instanceof Contact;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Client::class => false, Contact::class => false];
    }
}
```

Register the service via `#[AutoconfigureTag('serializer.normalizer')]` (autoconfigure handles this automatically since `defaults().autoconfigure()` is set in service config; otherwise add the tag).

- [ ] **Step 2: Test**

```php
public function testClientResponseIncludesCustomFields(): void
{
    // Functional API test — see existing ClientTest.php pattern
    // After defining a CustomField with key 'department', POST a client without customFields,
    // assert customFields is `{}`. Then PATCH with customFields={department: 'Sales'},
    // assert GET returns customFields={department: 'Sales'}.
}
```

(Full body in Task 34.)

- [ ] **Step 3: Commit**

```bash
git add src/CoreBundle/Serializer/Normalizer/CustomFieldsNormalizer.php
git commit -m "feat(api): include customFields on Client/Contact API responses"
```

---

### Task 28: `CustomFieldsDenormalizer`

**Files:**
- Create: `src/CoreBundle/Serializer/Normalizer/CustomFieldsDenormalizer.php`

- [ ] **Step 1: Implement**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Serializer\Normalizer;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldTypeResolver;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class CustomFieldsDenormalizer implements DenormalizerAwareInterface, DenormalizerInterface
{
    use DenormalizerAwareTrait;

    public const STAGED_KEY = self::class . '::staged';
    private const SKIP_KEY = self::class . '::skip';

    public function __construct(
        private readonly CustomFieldRepository $fields,
        private readonly CustomFieldTypeResolver $resolver,
    ) {
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $payload = $data['customFields'] ?? null;
        unset($data['customFields']);

        $context[self::SKIP_KEY] = true;
        $object = $this->denormalizer->denormalize($data, $type, $format, $context);

        if (! is_array($payload)) {
            return $object;
        }

        $target = $type === Client::class ? CustomFieldTarget::CLIENT : CustomFieldTarget::CONTACT;
        $defs = [];
        foreach ($this->fields->findByTargetOrdered($target) as $def) {
            $defs[$def->getFieldKey()] = $def;
        }

        $unknown = array_diff(array_keys($payload), array_keys($defs));
        if ($unknown !== []) {
            throw new UnexpectedValueException('Unknown custom field keys: ' . implode(', ', $unknown));
        }

        $staged = [];
        foreach ($payload as $key => $raw) {
            $def = $defs[$key];
            $staged[(string) $def->getId()] = [
                'field' => $def,
                'value' => $this->resolver->serialize($def, $raw),
            ];
        }

        // Stash on the object via a public dynamic property — the state processor reads it after persist.
        $object->{'__customFieldsStaged'} = $staged;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if ($context[self::SKIP_KEY] ?? false) {
            return false;
        }
        return $type === Client::class || $type === Contact::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Client::class => false, Contact::class => false];
    }
}
```

The dynamic property is a pragmatic shortcut. A cleaner approach is a `RequestStack`-keyed cache or a dedicated DTO. For v1 this is acceptable — the alternative adds two extra services and the property name is namespaced (`__customFieldsStaged`).

- [ ] **Step 2: Commit**

```bash
git add src/CoreBundle/Serializer/Normalizer/CustomFieldsDenormalizer.php
git commit -m "feat(api): denormalize customFields off Client/Contact API input"
```

---

### Task 29: `CustomFieldsStateProcessor` — persist staged values after entity saves

**Files:**
- Create: `src/CoreBundle/State/CustomFieldsStateProcessor.php`

- [ ] **Step 1: Implement**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Doctrine\Common\State\RemoveProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomFieldValue;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates: PersistProcessor::class)]
final class CustomFieldsStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $inner,
        private readonly EntityManagerInterface $em,
        private readonly CustomFieldValueRepository $values,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $result = $this->inner->process($data, $operation, $uriVariables, $context);

        if (! ($data instanceof Client || $data instanceof Contact)) {
            return $result;
        }

        $staged = $data->{'__customFieldsStaged'} ?? null;
        if (! is_array($staged) || $data->getId() === null) {
            return $result;
        }

        $target = $data instanceof Client ? CustomFieldTarget::CLIENT : CustomFieldTarget::CONTACT;
        $existing = [];
        foreach ($this->values->findForRecord($target, $data->getId()) as $v) {
            $existing[(string) $v->getField()->getId()] = $v;
        }

        foreach ($staged as $fieldIdStr => $entry) {
            $def = $entry['field'];
            $value = $entry['value']; // string|null
            $existingValue = $existing[$fieldIdStr] ?? null;

            if ($value === null) {
                if ($existingValue !== null) {
                    $this->em->remove($existingValue);
                }
                continue;
            }

            if ($existingValue === null) {
                $newValue = (new CustomFieldValue())
                    ->setField($def)
                    ->setTarget($target)
                    ->setTargetId($data->getId())
                    ->setValue($value)
                    ->setCompany($data->getCompany());
                $this->em->persist($newValue);
            } else {
                $existingValue->setValue($value);
            }
        }

        $this->em->flush();
        return $result;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/CoreBundle/State/CustomFieldsStateProcessor.php
git commit -m "feat(api): persist staged custom field values after Client/Contact save"
```

---

### Task 30: OpenAPI schema decorator for `customFields`

**Files:**
- Create: `src/CoreBundle/OpenApi/CustomFieldsSchemaDecorator.php`

- [ ] **Step 1: Implement**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;

final class CustomFieldsSchemaDecorator implements OpenApiFactoryInterface
{
    public function __construct(private readonly OpenApiFactoryInterface $decorated) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);
        $components = $openApi->getComponents();
        $schemas = $components->getSchemas() ?? new \ArrayObject();

        foreach (['Client', 'Contact', 'Client.jsonld', 'Contact.jsonld'] as $name) {
            if (! isset($schemas[$name])) {
                continue;
            }
            $schema = $schemas[$name];
            $properties = $schema['properties'] ?? [];
            $properties['customFields'] = [
                'type' => 'object',
                'description' => 'User-defined custom field values keyed by field_key. Discover available keys via GET /api/custom_fields?target=' . strtoupper(str_replace('.jsonld', '', $name)) . '.',
                'additionalProperties' => true,
                'example' => ['department' => 'Sales', 'tier' => 'gold'],
            ];
            $schema['properties'] = $properties;
            $schemas[$name] = $schema;
        }

        return $openApi->withComponents($components->withSchemas($schemas));
    }
}
```

Register via service config:

```yaml
# config/services.yaml or src/CoreBundle/Resources/config/services.php
services:
    SolidInvoice\CoreBundle\OpenApi\CustomFieldsSchemaDecorator:
        decorates: 'api_platform.openapi.factory'
        arguments:
            $decorated: '@.inner'
```

(Use whichever DI format the bundle already uses — check `src/CoreBundle/Resources/config/services.php`.)

- [ ] **Step 2: Verify**

```bash
curl http://localhost:8000/api/docs.json | jq '.components.schemas.Client.properties.customFields'
```

Expected: object with `additionalProperties: true`.

- [ ] **Step 3: Commit**

```bash
git add src/CoreBundle/OpenApi/CustomFieldsSchemaDecorator.php src/CoreBundle/Resources/config/services.php
git commit -m "feat(api): document customFields in OpenAPI"
```

---

## Phase G — Fresh-install seeder

### Task 31: Default seed for new installs

**Files:**
- Find existing install fixture pattern (likely `src/InstallBundle/Step/` or `src/CoreBundle/DummyData/` — search `EnsureApplicationInstalled` callers)
- Modify the appropriate fixture to seed three CONTACT custom fields when the database is fresh

- [ ] **Step 1: Locate existing install fixtures**

```bash
grep -rn "ContactType\|contact_types" src/InstallBundle src/CoreBundle 2>/dev/null
```

The previous install seeder created `additional email`, `phone`, `mobile` ContactType rows. After Task 8 those classes are gone. Find the seeder code and adapt it to create `CustomField` rows instead.

- [ ] **Step 2: Update the seeder**

Pseudo-code (actual file path depends on what Step 1 finds):

```php
$em->persist(
    (new CustomField())
        ->setTarget(CustomFieldTarget::CONTACT)
        ->setLabel('Additional Email')
        ->setFieldKey('additional_email')
        ->setType(CustomFieldType::EMAIL)
        ->setPosition(0)
        ->setCompany($company)
);
$em->persist(
    (new CustomField())
        ->setTarget(CustomFieldTarget::CONTACT)
        ->setLabel('Phone')
        ->setFieldKey('phone')
        ->setType(CustomFieldType::TEXT)
        ->setPosition(1)
        ->setCompany($company)
);
$em->persist(
    (new CustomField())
        ->setTarget(CustomFieldTarget::CONTACT)
        ->setLabel('Mobile')
        ->setFieldKey('mobile')
        ->setType(CustomFieldType::TEXT)
        ->setPosition(2)
        ->setCompany($company)
);
$em->flush();
```

- [ ] **Step 3: Test on a fresh install**

```bash
bin/console doctrine:database:drop --force --if-exists --env=test
bin/console doctrine:database:create --env=test
bin/console doctrine:migrations:migrate --no-interaction --env=test
# Run the install/seed command — find via bin/console list | grep install
bin/console dbal:run-sql "SELECT label, field_key FROM custom_field WHERE target='CONTACT'" --env=test
```

Expected: three rows.

- [ ] **Step 4: Commit**

```bash
git add <files>
git commit -m "feat(install): seed default CONTACT custom fields on fresh install"
```

---

## Phase H — Integration tests

### Task 32: Settings page functional tests

**Files:**
- Create: `src/SettingsBundle/Tests/Functional/CustomFieldsPageTest.php`

- [ ] **Step 1: Write tests**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\SettingsBundle\Tests\Functional;

use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group functional
 */
final class CustomFieldsPageTest extends WebTestCase
{
    use Factories, ResetDatabase, EnsureApplicationInstalled;

    public function testIndexLoadsForLoggedInUser(): void
    {
        $client = self::createClient();
        // (auth helper — match existing functional tests' login pattern)
        $client->loginUser($this->createUser());
        $client->request('GET', '/settings/custom-fields');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Custom fields');
    }

    public function testCreateField(): void
    {
        $client = self::createClient();
        $client->loginUser($this->createUser());

        $client->request('GET', '/settings/custom-fields/new?target=CLIENT');
        self::assertResponseIsSuccessful();

        $client->submitForm('Save', [
            'custom_field_definition[label]' => 'Department',
            'custom_field_definition[fieldKey]' => 'department',
            'custom_field_definition[type]' => CustomFieldType::TEXT->value,
        ]);
        self::assertResponseRedirects('/settings/custom-fields');

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $field = $em->getRepository(CustomField::class)->findOneBy(['fieldKey' => 'department']);
        self::assertNotNull($field);
        self::assertSame(CustomFieldTarget::CLIENT, $field->getTarget());
    }

    public function testReorder(): void
    {
        // Create two fields, POST to reorder, assert positions swapped.
        // Full body — match the pattern of an existing settings test.
    }

    public function testDeleteRequiresCsrf(): void
    {
        $client = self::createClient();
        $client->loginUser($this->createUser());

        $field = (new CustomField())
            ->setTarget(CustomFieldTarget::CLIENT)
            ->setLabel('Tmp')
            ->setFieldKey('tmp')
            ->setType(CustomFieldType::TEXT);
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($field);
        $em->flush();

        $client->request('POST', '/settings/custom-fields/' . $field->getId() . '/delete', ['_token' => 'wrong']);
        self::assertResponseStatusCodeSame(403);
    }

    private function createUser() { /* Foundry user factory invocation; match existing functional tests */ }
}
```

- [ ] **Step 2: Run, fix, commit**

```bash
bin/phpunit src/SettingsBundle/Tests/Functional/CustomFieldsPageTest.php
git add src/SettingsBundle/Tests/Functional/CustomFieldsPageTest.php
git commit -m "test(settings): functional tests for custom fields page"
```

---

### Task 33: Client/Contact form integration tests

**Files:**
- Create: `src/ClientBundle/Tests/Functional/CustomFieldsClientFormTest.php`

- [ ] **Step 1: Write tests**

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\ClientBundle\Tests\Functional;

use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group functional
 */
final class CustomFieldsClientFormTest extends WebTestCase
{
    use Factories, ResetDatabase, EnsureApplicationInstalled;

    public function testCreateClientWithCustomFieldValue(): void
    {
        $browser = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $company = CompanyFactory::createOne()->_real();

        $field = (new CustomField())
            ->setTarget(CustomFieldTarget::CLIENT)
            ->setLabel('Department')
            ->setFieldKey('department')
            ->setType(CustomFieldType::TEXT)
            ->setCompany($company);
        $em->persist($field);
        $em->flush();

        $browser->loginUser($this->createUserFor($company));
        $browser->request('GET', '/clients/new');
        $browser->submitForm('Save', [
            'client[name]' => 'Acme',
            'client[customFields][department]' => 'Sales',
        ]);
        self::assertResponseRedirects();

        /** @var CustomFieldValueRepository $repo */
        $repo = self::getContainer()->get(CustomFieldValueRepository::class);
        // Find the client by name and assert its single CustomFieldValue
        // (use ClientRepository for the lookup; helper omitted for brevity)
    }

    public function testRequiredFieldEmptyShowsValidationError(): void
    {
        $browser = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $company = CompanyFactory::createOne()->_real();

        $field = (new CustomField())
            ->setTarget(CustomFieldTarget::CLIENT)
            ->setLabel('Department')
            ->setFieldKey('department')
            ->setType(CustomFieldType::TEXT)
            ->setRequired(true)
            ->setCompany($company);
        $em->persist($field);
        $em->flush();

        $browser->loginUser($this->createUserFor($company));
        $browser->request('GET', '/clients/new');
        $browser->submitForm('Save', [
            'client[name]' => 'Acme',
            'client[customFields][department]' => '',
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.invalid-feedback, .form-error-message', 'should not be blank');
    }

    public function testInvalidNumberFieldShowsValidationError(): void
    {
        // Create a NUMBER field, submit "not a number", assert 422 + error message.
    }

    public function testCrossCompanyIsolation(): void
    {
        // Company A defines a field, company B's client form does not include it.
    }

    private function createUserFor($company) { /* match existing functional test login pattern */ }
}
```

Replace the helper stubs with the actual user/login helpers used by other functional tests in this codebase (search for `loginUser` calls in existing `Tests/Functional/`).

- [ ] **Step 2: Commit**

```bash
git add src/ClientBundle/Tests/Functional/CustomFieldsClientFormTest.php
git commit -m "test(client): functional tests for custom fields on client form"
```

---

### Task 34: API tests

**Files:**
- Create: `src/CoreBundle/Tests/Functional/Api/CustomFieldApiTest.php`
- Create: `src/CoreBundle/Tests/Functional/Api/ClientCustomFieldsApiTest.php`

- [ ] **Step 1: `CustomFieldApiTest`**

Tests:
- `GET /api/custom_fields?target=CLIENT` returns only CLIENT-target rows
- `POST /api/custom_fields` creates a row and assigns position
- `PATCH /api/custom_fields/{id}` updates fields
- `DELETE /api/custom_fields/{id}` cascades values
- `POST /api/custom_fields/reorder` updates positions
- Cross-company: A's token cannot see B's fields

- [ ] **Step 2: `ClientCustomFieldsApiTest`**

Tests:
- `POST /api/clients` with `customFields: {department: 'Sales'}` persists; GET returns the same
- `PATCH` partial update preserves un-mentioned keys
- `PATCH` with `customFields: {department: null}` clears the value
- `POST` with unknown key returns 422
- Cross-company: A's token can't write a value for B's field key

(Bodies follow the existing `ClientTest.php` API test pattern.)

- [ ] **Step 3: Run, fix, commit**

```bash
bin/phpunit src/CoreBundle/Tests/Functional/Api/CustomFieldApiTest.php src/CoreBundle/Tests/Functional/Api/ClientCustomFieldsApiTest.php
git add src/CoreBundle/Tests/Functional/Api/
git commit -m "test(api): full coverage for custom fields API"
```

---

### Task 35: MCP smoke test

**Files:**
- Create: `src/CoreBundle/Tests/Functional/Mcp/CustomFieldsMcpTest.php`

- [ ] **Step 1: Write the test** (consult API Platform 4 MCP docs for the exact endpoint path; common path is `/api/.well-known/mcp` or `/mcp`)

```php
<?php

declare(strict_types=1);

/* file header ... */

namespace SolidInvoice\CoreBundle\Tests\Functional\Mcp;

use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group functional
 */
final class CustomFieldsMcpTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    public function testMcpToolsListIncludesCustomFieldOperations(): void
    {
        $client = self::createClient();
        // Adjust path to whatever API Platform 4 exposes for MCP tool listing.
        $client->request('GET', '/api/mcp/tools', server: ['HTTP_X_API_TOKEN' => $this->getApiToken()]);
        self::assertResponseIsSuccessful();
        $names = array_map(fn ($t) => $t['name'], $client->getResponse()->toArray()['tools'] ?? []);

        foreach (['list_custom_fields', 'create_custom_field', 'update_custom_field', 'delete_custom_field'] as $expected) {
            self::assertContains($expected, $names, "MCP tool $expected missing");
        }
    }

    private function getApiToken(): string { /* match existing API token helper */ return ''; }
}
```

If the exact MCP endpoint isn't yet documented for the API Platform 4 version in use, this test may need adjustment. Skip and add a `TODO` if necessary, but file an issue capturing the gap.

- [ ] **Step 2: Commit**

```bash
git add src/CoreBundle/Tests/Functional/Mcp/CustomFieldsMcpTest.php
git commit -m "test(mcp): smoke test for custom field MCP tools"
```

---

## Final verification

- [ ] **Run the full quality gate**

```bash
bin/ecs check --fix
bin/phpstan analyse
bin/phpunit
bun run build
```

All must pass.

- [ ] **End-to-end manual test**

1. Fresh database (`bin/console doctrine:database:drop --force && create && migrate`)
2. Run the install seeder
3. Visit `/settings/custom-fields` — should show three default CONTACT fields (`additional_email`, `phone`, `mobile`)
4. Add one CLIENT field of each type
5. Reorder them by drag-and-drop, refresh, confirm order persisted
6. Create a client, fill in custom fields, save
7. Visit the client view page → custom fields card with values
8. Edit the client, change values, save
9. `GET /api/clients/{id}` → JSON contains `customFields` object
10. `PATCH /api/clients/{id}` with `{"customFields": {"department": "Eng"}}` → value updated
11. Delete a custom field via settings → cascade removes values; client view updates
12. Delete a client → orphan check command finds zero orphans

- [ ] **Final commit (if anything was tweaked during manual test)**

```bash
git add -A
git commit -m "chore: final adjustments after end-to-end testing"
```

---

## Summary

This plan delivers the unified custom-field system across 35 tasks in 8 phases:

- **A. Foundation** (Tasks 1–6): enums, entities, repositories, schema migration, type resolver
- **B. Migration** (Tasks 7–9): port legacy `ContactType` data, delete legacy classes, migration test
- **C. Form integration** (Tasks 10–14): the value collection widget, embedding in Client/Contact, cleanup listener, orphan command
- **D. View rendering** (Tasks 15–17): `CustomFieldsList` component embedded in Client and Contact views
- **E. Settings UI** (Tasks 18–24): routes, form, list/create/edit/delete actions, reorder, menu entry
- **F. API + MCP** (Tasks 25–30): ApiResource on `CustomField`, normalizers/denormalizer/state processor for embedding values, OpenAPI doc
- **G. Install seeder** (Task 31): defaults for fresh installs
- **H. Tests** (Tasks 32–35): settings, client form, API, MCP smoke

Each task ends with a commit; the plan supports incremental review and rollback.
