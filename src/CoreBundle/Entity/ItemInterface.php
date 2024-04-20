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

use Brick\Math\BigInteger;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\TaxBundle\Entity\Tax;

interface ItemInterface
{
    public function getId(): UuidInterface;

    public function setDescription(string $description): self;

    public function getDescription(): ?string;

    public function setPrice(BigInteger|float|int|string $price): self;

    public function getPrice(): BigInteger;

    public function setQty(float $qty): self;

    public function getQty(): ?float;

    public function setTotal(BigInteger|float|int|string $total): self;

    public function getTotal(): BigInteger;

    public function getTax(): ?Tax;

    public function setTax(?Tax $tax): self;
}
