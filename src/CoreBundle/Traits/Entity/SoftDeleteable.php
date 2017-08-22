<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Traits\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use Doctrine\ORM\Mapping as ORM;

trait SoftDeleteable
{
    /**
     * @ORM\Column(type="datetime", name="deleted", nullable=true)
     * @ApiProperty(iri="https://schema.org/DateTime")
     */
    protected $deletedAt;

    /**
     * Returns deletedAt.
     *
     * @return \DateTime
     */
    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

    /**
     * Sets deletedAt.
     *
     * @param \Datetime|null $deletedAt
     *
     * @return $this
     */
    public function setDeletedAt(\DateTime $deletedAt = null)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return bool
     * @ApiProperty(iri="https://schema.org/Boolean")
     */
    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }
}
