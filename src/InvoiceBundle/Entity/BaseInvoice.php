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

namespace SolidInvoice\InvoiceBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use LogicException;
use Money\Currency;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use function sprintf;

#[ORM\MappedSuperclass]
abstract class BaseInvoice
{
    use CompanyAware;

    #[ORM\Column(name: 'total_amount', type: BigIntegerType::NAME)]
    #[Groups(['invoice_api:read', 'recurring_invoice_api:read', 'searchable'])]
    #[ApiProperty(
        writable: false,
        openapiContext: [
            'type' => 'number',
        ],
        jsonSchemaContext: [
            'type' => 'number',
        ]
    )]
    protected BigNumber $total;

    #[ORM\Column(name: 'baseTotal_amount', type: BigIntegerType::NAME)]
    #[Groups(['invoice_api', 'recurring_invoice_api', 'client_api', 'invoice_api:read', 'recurring_invoice_api:read'])]
    #[ApiProperty(
        writable: false,
        openapiContext: [
            'type' => 'number',
        ],
        jsonSchemaContext: [
            'type' => 'number',
        ]
    )]
    protected BigNumber $baseTotal;

    #[ORM\Column(name: 'tax_amount', type: BigIntegerType::NAME)]
    #[Groups(['invoice_api', 'recurring_invoice_api', 'client_api', 'invoice_api:read', 'recurring_invoice_api:read'])]
    #[ApiProperty(
        writable: false,
        openapiContext: [
            'type' => 'number',
        ],
        jsonSchemaContext: [
            'type' => 'number',
        ]
    )]
    protected BigNumber $tax;

    #[ORM\Embedded(class: Discount::class)]
    #[Groups(['invoice_api', 'recurring_invoice_api', 'client_api', 'create_invoice_api', 'create_recurring_invoice_api', 'invoice_api:read', 'invoice_api:write', 'recurring_invoice_api:read', 'recurring_invoice_api:write'])]
    #[ApiProperty(
        openapiContext: [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'oneOf' => [
                        ['type' => 'string', 'enum' => ['percentage', 'money']],
                        ['type' => 'null'],
                    ],
                ],
                'value' => [
                    'oneOf' => [
                        ['type' => 'number'],
                        ['type' => 'null'],
                    ],
                ],
            ],
        ],
        jsonSchemaContext: [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'oneOf' => [
                        ['type' => 'string', 'enum' => ['percentage', 'money']],
                        ['type' => 'null'],
                    ],
                ],
                'value' => [
                    'oneOf' => [
                        ['type' => 'number'],
                        ['type' => 'null'],
                    ],
                ],
            ],
        ]
    )]
    protected Discount $discount;

    #[ORM\Column(name: 'terms', type: Types::TEXT, nullable: true)]
    #[Groups(['invoice_api', 'recurring_invoice_api', 'client_api', 'create_invoice_api', 'create_recurring_invoice_api', 'invoice_api:read', 'invoice_api:write', 'recurring_invoice_api:read', 'recurring_invoice_api:write'])]
    protected ?string $terms = null;

    #[ORM\Column(name: 'notes', type: Types::TEXT, nullable: true)]
    #[Groups(['invoice_api', 'recurring_invoice_api', 'client_api', 'create_invoice_api', 'create_recurring_invoice_api', 'invoice_api:read', 'invoice_api:write', 'recurring_invoice_api:read', 'recurring_invoice_api:write'])]
    protected ?string $notes = null;

    #[ORM\Column(name: 'withholding_amount', type: BigIntegerType::NAME, options: ['default' => 0])]
    #[Groups(['invoice_api:read', 'recurring_invoice_api:read'])]
    #[ApiProperty(
        writable: false,
        openapiContext: [
            'type' => 'number',
        ],
        jsonSchemaContext: [
            'type' => 'number',
        ]
    )]
    protected BigNumber $withholdingAmount;

    #[ORM\Column(name: 'payable_amount', type: BigIntegerType::NAME, options: ['default' => 0])]
    #[Groups(['invoice_api:read', 'recurring_invoice_api:read'])]
    #[ApiProperty(
        writable: false,
        openapiContext: [
            'type' => 'number',
        ],
        jsonSchemaContext: [
            'type' => 'number',
        ]
    )]
    protected BigNumber $payableAmount;

    /**
     * The currency the document was issued in, frozen the moment it leaves Draft/New.
     * Null on a draft, or on a row written before this column existed — both fall back
     * to {@see self::getClient()}'s current currency in {@see self::getCurrency()}.
     */
    #[ORM\Column(name: 'currency_code', type: Types::STRING, length: 3, nullable: true)]
    #[Assert\Currency]
    protected ?string $currencyCode = null;

    public function __construct()
    {
        $this->discount = new Discount();
        $this->baseTotal = BigDecimal::zero();
        $this->tax = BigDecimal::zero();
        $this->total = BigDecimal::zero();
        $this->withholdingAmount = BigInteger::zero();
        $this->payableAmount = BigInteger::zero();
    }

    public function getTotal(): BigNumber
    {
        return $this->total;
    }

    /**
     * @throws MathException
     */
    public function setTotal(BigNumber | float | int | string $total): self
    {
        $this->total = BigNumber::of($total);

        return $this;
    }

    public function getBaseTotal(): BigNumber
    {
        return $this->baseTotal;
    }

    /**
     * @throws MathException
     */
    public function setBaseTotal(BigNumber | float | int | string $baseTotal): self
    {
        $this->baseTotal = BigNumber::of($baseTotal);

        return $this;
    }

    public function getDiscount(): Discount
    {
        return $this->discount;
    }

    public function setDiscount(Discount $discount): self
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * @throws MathException
     */
    public function hasDiscount(): bool
    {
        $value = $this->discount->getValue();

        return BigNumber::of(is_float($value) ? (string) $value : $value)->isPositive();
    }

    public function getTerms(): ?string
    {
        return $this->terms;
    }

    public function setTerms(?string $terms): self
    {
        $this->terms = $terms;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getTax(): BigNumber
    {
        return $this->tax;
    }

    /**
     * @throws MathException
     */
    public function setTax(BigNumber | float | int | string $tax): self
    {
        $this->tax = BigNumber::of($tax);

        return $this;
    }

    public function getWithholdingAmount(): BigNumber
    {
        return $this->withholdingAmount;
    }

    /**
     * @throws MathException
     */
    public function setWithholdingAmount(BigNumber | float | int | string $withholdingAmount): self
    {
        $this->withholdingAmount = BigNumber::of($withholdingAmount);

        return $this;
    }

    public function getPayableAmount(): BigNumber
    {
        return $this->payableAmount;
    }

    /**
     * @throws MathException
     */
    public function setPayableAmount(BigNumber | float | int | string $payableAmount): self
    {
        $this->payableAmount = BigNumber::of($payableAmount);

        return $this;
    }

    abstract public function getClient(): ?Client;

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(?string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    /**
     * The frozen currency once the document has left Draft/New, otherwise the client's
     * current currency. Templates must read this instead of `client.currency` directly,
     * so an issued document keeps stating the amount it stated when it was issued.
     */
    public function getCurrency(): Currency
    {
        if ($this->currencyCode !== null) {
            return new Currency($this->currencyCode);
        }

        $client = $this->getClient();

        if (! $client instanceof Client) {
            throw new LogicException(sprintf('%s has no client to resolve a currency from.', static::class));
        }

        return $client->getCurrency();
    }
}
