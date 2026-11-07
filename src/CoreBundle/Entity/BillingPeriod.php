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

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * EN 16931 BG-14 INVOICE PERIOD: the start (BT-73) and end (BT-74) date of the period an
 * invoice covers. Doctrine never hydrates this as null once it is mapped on an entity —
 * emptiness is {@see self::isEmpty()}, not a null check on the embeddable itself.
 *
 * No serialization groups here: {@see \SolidInvoice\ApiBundle\Serializer\Normalizer\BillingPeriodNormalizer}
 * claims the whole type and builds the wire shape itself, so per-property groups are never read.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Entity\BillingPeriodTest
 */
#[ORM\Embeddable]
class BillingPeriod
{
    #[ORM\Column(name: 'start', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $start = null;

    #[ORM\Column(name: 'end', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $end = null;

    public static function of(?DateTimeImmutable $start, ?DateTimeImmutable $end): self
    {
        return new self()
            ->setStart($start)
            ->setEnd($end);
    }

    public function getStart(): ?DateTimeImmutable
    {
        return $this->start;
    }

    public function setStart(?DateTimeImmutable $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?DateTimeImmutable
    {
        return $this->end;
    }

    public function setEnd(?DateTimeImmutable $end): self
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Both dates present — the only state EN 16931 BG-14 accepts.
     */
    public function isComplete(): bool
    {
        return $this->start instanceof DateTimeImmutable && $this->end instanceof DateTimeImmutable;
    }

    /**
     * Neither date present — an invoice that states no period.
     */
    public function isEmpty(): bool
    {
        return ! $this->start instanceof DateTimeImmutable && ! $this->end instanceof DateTimeImmutable;
    }
}
