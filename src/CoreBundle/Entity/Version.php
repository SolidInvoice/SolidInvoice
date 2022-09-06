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

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Version.
 *
 * @ORM\Entity(repositoryClass="SolidInvoice\CoreBundle\Repository\VersionRepository")
 * @ORM\Table(name="version")
 */
class Version
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="version", type="string", length=125)
     * @ORM\Id
     */
    private $version;

    /**
     * @param string $version
     */
    public function __construct(string $version = null)
    {
        $this->setVersion($version);
    }

    /**
     * Set version.
     *
     * @param string $version
     *
     * @return Version
     */
    public function setVersion(?string $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     *
     * @return string
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Return the version as a string.
     */
    public function __toString(): string
    {
        return $this->version;
    }
}
