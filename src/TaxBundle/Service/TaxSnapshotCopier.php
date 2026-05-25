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

namespace SolidInvoice\TaxBundle\Service;

use Brick\Math\Exception\MathException;
use DateTimeInterface;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Entity\LineTax;

/**
 * Copies LineTax/InvoiceTax rows to fresh entities, preserving snapshot fields verbatim.
 *
 * Used by the quote→invoice converter and the recurring invoice generator to ensure
 * generated documents own independent snapshot rows (not shared references) and remain
 * immutable even when master Tax rates change later.
 * @see \SolidInvoice\TaxBundle\Tests\Service\TaxSnapshotCopierTest
 */
final class TaxSnapshotCopier
{
    /**
     * @throws MathException
     */
    public function copyLineTax(LineTax $source, ?DateTimeInterface $freezeAt = null): LineTax
    {
        $copy = new LineTax();
        $copy->setTax($source->getTax());
        $copy->setNameSnapshot((string) $source->getNameSnapshot());
        $copy->setRateSnapshot($source->getRateSnapshot());
        $copy->setCategorySnapshot($source->getCategorySnapshot());
        $copy->setTypeSnapshot($source->getTypeSnapshot());
        $copy->setCompound($source->isCompound());
        $copy->setSequence($source->getSequence());
        $copy->setAmount($source->getAmount());

        if ($freezeAt instanceof DateTimeInterface) {
            $copy->freeze($freezeAt);
        } elseif ($source->getSnapshottedAt() instanceof DateTimeInterface) {
            $copy->setSnapshottedAt($source->getSnapshottedAt());
        }

        return $copy;
    }

    /**
     * @throws MathException
     */
    public function copyInvoiceTax(InvoiceTax $source, ?DateTimeInterface $freezeAt = null): InvoiceTax
    {
        $copy = new InvoiceTax();
        $copy->setTax($source->getTax());
        $copy->setDirection($source->getDirection());
        $copy->setNameSnapshot((string) $source->getNameSnapshot());
        $copy->setRateSnapshot($source->getRateSnapshot());
        $copy->setCategorySnapshot($source->getCategorySnapshot());
        $copy->setAmount($source->getAmount());
        $copy->setNote($source->getNote());
        $copy->setSequence($source->getSequence());

        if ($freezeAt instanceof DateTimeInterface) {
            $copy->freeze($freezeAt);
        } elseif ($source->getSnapshottedAt() instanceof DateTimeInterface) {
            $copy->setSnapshottedAt($source->getSnapshottedAt());
        }

        return $copy;
    }
}
