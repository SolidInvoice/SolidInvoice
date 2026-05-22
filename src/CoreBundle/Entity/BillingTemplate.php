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

namespace SolidInvoice\CoreBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Repository\BillingTemplateRepository;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A user-managed Twig template used to render an invoice or quote variant
 * (in-app HTML view, mPDF document or e-mail body).
 *
 * Each (company, type, variant) tuple has a "system" template — derived from
 * the shipped defaults during installation — plus zero or more custom
 * templates. Exactly one row per tuple has {@see self::$active} set to true.
 */
#[ORM\Table(name: BillingTemplate::TABLE_NAME)]
#[ORM\Index(name: 'billing_templates_lookup_idx', columns: ['company_id', 'type', 'variant'])]
#[ORM\Entity(repositoryClass: BillingTemplateRepository::class)]
class BillingTemplate implements Stringable
{
    final public const TABLE_NAME = 'billing_templates';

    final public const TYPE_INVOICE = 'invoice';

    final public const TYPE_QUOTE = 'quote';

    final public const VARIANT_HTML = 'html';

    final public const VARIANT_PDF = 'pdf';

    final public const VARIANT_EMAIL = 'email';

    /**
     * Name reserved for the seeded default template. The UI prevents renaming
     * or deleting rows that carry this combination of {@see self::$name} and
     * {@see self::$system}.
     */
    final public const DEFAULT_NAME = 'Default';

    use CompanyAware;
    use TimeStampable;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 16)]
    #[Assert\Choice(choices: [self::TYPE_INVOICE, self::TYPE_QUOTE])]
    private string $type = self::TYPE_INVOICE;

    #[ORM\Column(name: 'variant', type: Types::STRING, length: 16)]
    #[Assert\Choice(choices: [self::VARIANT_HTML, self::VARIANT_PDF, self::VARIANT_EMAIL])]
    private string $variant = self::VARIANT_HTML;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 100)]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 100)]
    private string $name = '';

    #[ORM\Column(name: 'content', type: Types::TEXT)]
    private string $content = '';

    #[ORM\Column(name: 'active', type: Types::BOOLEAN)]
    private bool $active = false;

    #[ORM\Column(name: 'system', type: Types::BOOLEAN)]
    private bool $system = false;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getVariant(): string
    {
        return $this->variant;
    }

    public function setVariant(string $variant): self
    {
        $this->variant = $variant;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function isSystem(): bool
    {
        return $this->system;
    }

    public function setSystem(bool $system): self
    {
        $this->system = $system;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
