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

use Doctrine\DBAL\Types\Types;
use SolidInvoice\CoreBundle\Repository\VersionRepository;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

#[ORM\Table(name: Version::TABLE_NAME)]
#[ORM\Entity(repositoryClass: VersionRepository::class)]
class Version implements Stringable
{
    final public const TABLE_NAME = 'version';

    #[ORM\Column(name: 'version', type: Types::STRING, length: 125)]
    #[ORM\Id]
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
