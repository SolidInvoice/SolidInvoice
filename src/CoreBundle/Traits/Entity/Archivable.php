<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Traits\Entity;

use Doctrine\ORM\Mapping as ORM;

trait Archivable
{
    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", name="archived", nullable=true)
     */
    protected $archived;

    /**
     * @return bool
     */
    public function isArchived()
    {
        return true === $this->archived;
    }

    /**
     * @param bool $archived
     *
     * @return $this
     */
    public function setArchived($archived)
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Archives a record.
     *
     * @return Archivable
     */
    public function archive()
    {
        return $this->setArchived(true);
    }
}
