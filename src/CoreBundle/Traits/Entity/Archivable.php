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

use ApiPlatform\Core\Annotation\ApiProperty;
use Doctrine\ORM\Mapping as ORM;

trait Archivable
{
    /**
     * @ORM\Column(type="boolean", name="archived", nullable=true)
     * @ApiProperty(iri="https://schema.org/Boolean")
     */
    protected ?bool $archived = null;

    public function isArchived(): bool
    {
        return $this->archived;
    }

    /**
     * @param bool $archived
     *
     * @return $this
     */
    public function setArchived(?bool $archived)
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Archives a record.
     *
     * @return $this|Archivable
     */
    public function archive(): self
    {
        return $this->setArchived(true);
    }
}
