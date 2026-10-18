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

namespace SolidInvoice\PaymentBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\PaymentBundle\Enum\PaymentMeans;
use SolidInvoice\PaymentBundle\Repository\PaymentInstructionRepository;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @see \SolidInvoice\PaymentBundle\Tests\Entity\PaymentInstructionTest
 */
#[ORM\Table(name: PaymentInstruction::TABLE_NAME)]
#[ORM\Entity(repositoryClass: PaymentInstructionRepository::class)]
#[UniqueEntity(fields: ['company', 'label'])]
#[ApiResource(
    shortName: 'PaymentInstruction',
    operations: [
        new GetCollection(),
        new Post(),
        new Get(),
        new Patch(),
        new Delete(),
    ],
    normalizationContext: [
        'groups' => ['payment_instruction_api:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        'groups' => ['payment_instruction_api:write'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
)]
class PaymentInstruction implements Stringable
{
    final public const string TABLE_NAME = 'payment_instructions';

    use CompanyAware;
    use TimeStampable;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Groups(['payment_instruction_api:read'])]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'label', type: Types::STRING, length: 64)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    #[Groups(['payment_instruction_api:read', 'payment_instruction_api:write'])]
    private string $label;

    #[ORM\Column(name: 'means', type: Types::STRING, length: 32, enumType: PaymentMeans::class, options: ['default' => PaymentMeans::BankTransfer->value])]
    #[Groups(['payment_instruction_api:read', 'payment_instruction_api:write'])]
    private PaymentMeans $means = PaymentMeans::BankTransfer;

    #[ORM\Column(name: 'account_name', type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['payment_instruction_api:read', 'payment_instruction_api:write'])]
    private ?string $accountName = null;

    #[ORM\Column(name: 'account_identifier', type: Types::STRING, length: 34, nullable: true)]
    #[Assert\Length(max: 34)]
    #[Assert\When(
        expression: 'this.getMeans().value in ["bank_transfer", "sepa_credit_transfer"]',
        constraints: [new Assert\NotBlank(message: 'payment.constraint.instruction.account_identifier.required')],
    )]
    #[Assert\When(
        expression: 'this.getMeans().value in ["sepa_credit_transfer", "sepa_direct_debit"]',
        constraints: [new Assert\Iban()],
    )]
    #[Groups(['payment_instruction_api:read', 'payment_instruction_api:write'])]
    private ?string $accountIdentifier = null;

    #[ORM\Column(name: 'service_provider_identifier', type: Types::STRING, length: 35, nullable: true)]
    #[Assert\Length(max: 35)]
    #[Assert\When(
        expression: 'this.getMeans().value in ["sepa_credit_transfer", "sepa_direct_debit"]',
        constraints: [new Assert\Bic()],
    )]
    #[Groups(['payment_instruction_api:read', 'payment_instruction_api:write'])]
    private ?string $serviceProviderIdentifier = null;

    #[ORM\Column(name: 'bank_name', type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['payment_instruction_api:read', 'payment_instruction_api:write'])]
    private ?string $bankName = null;

    #[ORM\Column(name: 'mandate_reference', type: Types::STRING, length: 35, nullable: true)]
    #[Assert\Length(max: 35)]
    #[Groups(['payment_instruction_api:read', 'payment_instruction_api:write'])]
    private ?string $mandateReference = null;

    #[ORM\Column(name: 'creditor_identifier', type: Types::STRING, length: 35, nullable: true)]
    #[Assert\Length(max: 35)]
    #[Assert\When(
        expression: 'this.getMeans().value in ["sepa_direct_debit", "direct_debit"]',
        constraints: [new Assert\NotBlank(message: 'payment.constraint.instruction.creditor_identifier.required')],
    )]
    #[Groups(['payment_instruction_api:read', 'payment_instruction_api:write'])]
    private ?string $creditorIdentifier = null;

    #[ORM\Column(name: 'instructions', type: Types::TEXT, nullable: true)]
    #[Groups(['payment_instruction_api:read', 'payment_instruction_api:write'])]
    private ?string $instructions = null;

    #[ORM\Column(name: 'is_default', type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['payment_instruction_api:read', 'payment_instruction_api:write'])]
    private bool $default = false;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label ?? '';
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getMeans(): PaymentMeans
    {
        return $this->means;
    }

    public function setMeans(PaymentMeans $means): self
    {
        $this->means = $means;

        return $this;
    }

    public function getAccountName(): ?string
    {
        return $this->accountName;
    }

    public function setAccountName(?string $accountName): self
    {
        $this->accountName = $accountName;

        return $this;
    }

    public function getAccountIdentifier(): ?string
    {
        return $this->accountIdentifier;
    }

    public function setAccountIdentifier(?string $accountIdentifier): self
    {
        $this->accountIdentifier = $accountIdentifier;

        return $this;
    }

    public function getServiceProviderIdentifier(): ?string
    {
        return $this->serviceProviderIdentifier;
    }

    public function setServiceProviderIdentifier(?string $serviceProviderIdentifier): self
    {
        $this->serviceProviderIdentifier = $serviceProviderIdentifier;

        return $this;
    }

    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    public function setBankName(?string $bankName): self
    {
        $this->bankName = $bankName;

        return $this;
    }

    public function getMandateReference(): ?string
    {
        return $this->mandateReference;
    }

    public function setMandateReference(?string $mandateReference): self
    {
        $this->mandateReference = $mandateReference;

        return $this;
    }

    public function getCreditorIdentifier(): ?string
    {
        return $this->creditorIdentifier;
    }

    public function setCreditorIdentifier(?string $creditorIdentifier): self
    {
        $this->creditorIdentifier = $creditorIdentifier;

        return $this;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function setInstructions(?string $instructions): self
    {
        $this->instructions = $instructions;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function setDefault(bool $default): self
    {
        $this->default = $default;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s: %s', $this->label ?? '', $this->means->value);
    }
}
