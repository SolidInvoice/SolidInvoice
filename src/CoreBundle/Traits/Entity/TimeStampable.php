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

namespace SolidInvoice\CoreBundle\Traits\Entity;

use ApiPlatform\Metadata\ApiProperty;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Neither timestamp is in a serializer group here: a resource that exposes them
 * re-declares {@see self::getCreated()} and {@see self::getUpdated()} with its own read
 * group. A shared group would put them in every resource's group, and API Platform would
 * then embed each related resource instead of returning its IRI.
 */
trait TimeStampable
{
    #[Gedmo\Timestampable(on: 'create')]
    #[ApiProperty(writable: false, iris: ['https://schema.org/DateTime'])]
    #[ORM\Column(name: 'created', type: Types::DATETIME_IMMUTABLE)]
    protected ?DateTimeImmutable $created = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ApiProperty(writable: false, iris: ['https://schema.org/DateTime'])]
    #[ORM\Column(name: 'updated', type: Types::DATETIME_IMMUTABLE)]
    protected ?DateTimeImmutable $updated = null;

    /**
     * Returns created.
     */
    public function getCreated(): ?DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        // The column is immutable; DBAL 4 rejects mutable values, so normalise here
        // to accept any DateTimeInterface from the callers.
        $this->created = $created instanceof DateTimeImmutable ? $created : DateTimeImmutable::createFromInterface($created);

        return $this;
    }

    /**
     * Returns updated.
     */
    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(DateTimeInterface $updated): self
    {
        $this->updated = $updated instanceof DateTimeImmutable ? $updated : DateTimeImmutable::createFromInterface($updated);

        return $this;
    }
}
