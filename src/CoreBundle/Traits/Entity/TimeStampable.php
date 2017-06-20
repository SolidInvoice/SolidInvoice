<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Traits\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation as Serialize;
use Doctrine\ORM\Mapping as ORM;

trait TimeStampable
{
    /**
     * @var \DateTIme
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="datetime")
     * @ Serialize\Expose()
     * @ Serialize\Groups({"js"})
     * @ApiProperty(iri="https://schema.org/DateTime")
     */
    protected $created;

    /**
     * @var \DateTIme
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated", type="datetime")
     * @ Serialize\Expose()
     * @ Serialize\Groups({"js"})
     * @ApiProperty(iri="https://schema.org/DateTime")
     */
    protected $updated;

    /**
     * Returns created.
     *
     * @return \DateTime
     */
    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    /**
     * Sets created.
     *
     * @param \DateTime $created
     *
     * @return $this
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Returns updated.
     *
     * @return \DateTime
     */
    public function getUpdated(): \DateTime
    {
        return $this->updated;
    }

    /**
     * Sets updated.
     *
     * @param \DateTime $updated
     *
     * @return $this
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }
}
