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

use const PHP_INT_MAX;
use Brick\Math\BigNumber;
use Doctrine\Common\Collections\Collection;
use SolidInvoice\TaxBundle\Entity\LineTax;
use Symfony\Component\Uid\Ulid;

interface LineInterface
{
    /**
     * The position of a line whose owner has not placed it yet.
     *
     * A sentinel rather than null, so the property matches its `NOT NULL` column and a line
     * can never be persisted without a position. It is the largest int there is, so an
     * unplaced line sorts after every placed one and renumbering turns it into an append.
     */
    public const int UNPLACED = PHP_INT_MAX;

    public function getId(): Ulid;

    public function setName(string $name): self;

    public function getName(): string;

    /**
     * Setting a description on a line that has no name yet derives one from it, so a caller
     * that only knows about the description still produces a valid line.
     *
     * @see \SolidInvoice\CoreBundle\Billing\LineName
     */
    public function setDescription(?string $description): self;

    public function getDescription(): ?string;

    public function setPosition(int $position): self;

    /**
     * {@see self::UNPLACED} until an owner places the line.
     */
    public function getPosition(): int;

    public function setPrice(BigNumber | int | string $price): self;

    public function getPrice(): BigNumber;

    public function setQty(BigNumber | int | string $qty): self;

    public function getQty(): BigNumber;

    public function setTotal(BigNumber | int | string $total): self;

    public function getTotal(): BigNumber;

    public function addTax(LineTax $lineTax): static;

    public function removeTax(LineTax $lineTax): static;

    /**
     * @return Collection<int, LineTax>
     */
    public function getTaxes(): Collection;
}
