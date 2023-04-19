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

use Money\Money;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\TaxBundle\Entity\Tax;

interface ItemInterface
{
    public function getId(): UuidInterface;

    public function setDescription(string $description): self;

    public function getDescription(): ?string;

    public function setPrice(Money $price): self;

    public function getPrice(): ?Money;

    public function setQty(float $qty): self;

    public function getQty(): ?float;

    public function setTotal(Money $total): self;

    public function getTotal(): ?Money;

    public function getTax(): ?Tax;

    public function setTax(?Tax $tax): self;
}
