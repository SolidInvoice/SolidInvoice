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
use Doctrine\ORM\Mapping as ORM;

trait Archivable
{
    #[ApiProperty(iris: ['https://schema.org/Boolean'])]
    #[ORM\Column(name: 'archived', type: 'boolean', nullable: true)]
    protected ?bool $archived = null;

    public function isArchived(): bool
    {
        return $this->archived ?? false;
    }

    /**
     * @return $this
     */
    public function setArchived(?bool $archived)
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Archives a record.
     */
    public function archive(): self
    {
        return $this->setArchived(true);
    }
}
