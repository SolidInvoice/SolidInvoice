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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;
use Symfony\Component\Serializer\Annotation as Serialize;

#[ORM\MappedSuperclass]
abstract class BaseInvoice
{
    use CompanyAware;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 25)]
    #[Serialize\Groups(['invoice_api', 'recurring_invoice_api', 'client_api'])]
    protected ?string $status = null;

    #[ORM\Embedded(class: MoneyEntity::class)]
    #[Serialize\Groups(['invoice_api', 'recurring_invoice_api', 'client_api'])]
    protected MoneyEntity $total;

    #[ORM\Embedded(class: MoneyEntity::class)]
    #[Serialize\Groups(['invoice_api', 'recurring_invoice_api', 'client_api'])]
    protected MoneyEntity $baseTotal;

    #[ORM\Embedded(class: MoneyEntity::class)]
    #[Serialize\Groups(['invoice_api', 'recurring_invoice_api', 'client_api'])]
    protected MoneyEntity $tax;

    #[ORM\Embedded(class: Discount::class)]
    #[Serialize\Groups(['invoice_api', 'recurring_invoice_api', 'client_api', 'create_invoice_api', 'create_recurring_invoice_api'])]
    protected Discount $discount;

    #[ORM\Column(name: 'terms', type: Types::TEXT, nullable: true)]
    #[Serialize\Groups(['invoice_api', 'recurring_invoice_api', 'client_api', 'create_invoice_api', 'create_recurring_invoice_api'])]
    protected ?string $terms = null;

    #[ORM\Column(name: 'notes', type: Types::TEXT, nullable: true)]
    #[Serialize\Groups(['invoice_api', 'recurring_invoice_api', 'client_api', 'create_invoice_api', 'create_recurring_invoice_api'])]
    protected ?string $notes = null;

    public function __construct()
    {
        $this->discount = new Discount();
        $this->baseTotal = new MoneyEntity();
        $this->tax = new MoneyEntity();
        $this->total = new MoneyEntity();
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getTotal(): Money
    {
        return $this->total->getMoney();
    }

    public function setTotal(Money $total): self
    {
        $this->total = new MoneyEntity($total);

        return $this;
    }

    public function getBaseTotal(): Money
    {
        return $this->baseTotal->getMoney();
    }

    public function setBaseTotal(Money $baseTotal): self
    {
        $this->baseTotal = new MoneyEntity($baseTotal);

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

    public function getTax(): ?Money
    {
        return $this->tax->getMoney();
    }

    public function setTax(?Money $tax = null): self
    {
        $this->tax = $tax ? new MoneyEntity($tax) : null;

        return $this;
    }
}
