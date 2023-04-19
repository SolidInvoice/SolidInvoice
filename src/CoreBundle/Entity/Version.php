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
use Stringable;

/**
 * Class Version.
 *
 * @ORM\Entity(repositoryClass="SolidInvoice\CoreBundle\Repository\VersionRepository")
 * @ORM\Table(name="version")
 */
class Version implements Stringable
{
    /**
     * @ORM\Column(name="version", type="string", length=125)
     * @ORM\Id
     */
    private ?string $version = null;

    public function __construct(string $version = null)
    {
        $this->setVersion($version);
    }

    public function setVersion(?string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function __toString(): string
    {
        return $this->version;
    }
}
