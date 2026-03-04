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

use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Entity\Company;
use Symfony\Component\Serializer\Attribute as Serialize;
use Symfony\Component\Serializer\Attribute\Groups;

trait CompanyAware
{
    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Serialize\Ignore()]
    protected Company $company;

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;
        return $this;
    }

    #[Groups(['searchable'])]
    public function getCompanyId(): ?string
    {
        return isset($this->company) ? $this->company->getId()->toBase58() : null;
    }
}
