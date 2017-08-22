<?php

declare(strict_types = 1);

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

trait Archivable
{
    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", name="archived", nullable=true)
     * @ApiProperty(iri="https://schema.org/Boolean")
     */
    protected $archived;

    /**
     * @return bool
     */
    public function isArchived(): bool
    {
        return true === $this->archived;
    }

    /**
     * @param bool $archived
     *
     * @return $this
     */
    public function setArchived(? bool $archived)
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
