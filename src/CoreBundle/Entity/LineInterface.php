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

use Brick\Math\BigNumber;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Component\Uid\Ulid;

interface LineInterface
{
    public function getId(): Ulid;

    public function setDescription(string $description): self;

    public function getDescription(): ?string;

    public function setPrice(BigNumber|float|int|string $price): self;

    public function getPrice(): BigNumber;

    public function setQty(float $qty): self;

    public function getQty(): ?float;

    public function setTotal(BigNumber|float|int|string $total): self;

    public function getTotal(): BigNumber;

    public function getTax(): ?Tax;

    public function setTax(?Tax $tax): self;
}
