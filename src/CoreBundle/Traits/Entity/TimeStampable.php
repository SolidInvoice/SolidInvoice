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
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Attribute\Ignore;

trait TimeStampable
{
    #[Gedmo\Timestampable(on: 'create')]
    #[ApiProperty(iris: ['https://schema.org/DateTime'])]
    #[ORM\Column(name: 'created', type: 'datetime')]
    #[Ignore]
    protected ?DateTimeInterface $created = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ApiProperty(iris: ['https://schema.org/DateTime'])]
    #[ORM\Column(name: 'updated', type: 'datetime')]
    #[Ignore]
    protected ?DateTimeInterface $updated = null;

    /**
     * Returns created.
     */
    public function getCreated(): ?DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Returns updated.
     */
    public function getUpdated(): ?DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(DateTimeInterface $updated): self
    {
        $this->updated = $updated;

        return $this;
    }
}
