<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Version.
 *
 * @ORM\Entity(repositoryClass="CSBill\CoreBundle\Repository\VersionRepository")
 * @ORM\Table(name="version")
 */
class Version
{
    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=125, nullable=false)
     * @ORM\Id
     */
    private $version;

    /**
     * @param string $version
     */
    public function __construct($version = null)
    {
        $this->setVersion($version);
    }

    /**
     * Set version.
     *
     * @param string $version
     *
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Return the version as a string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->version;
    }
}
