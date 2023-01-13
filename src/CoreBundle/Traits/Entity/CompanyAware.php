<?php
declare(strict_types=1);

namespace SolidInvoice\CoreBundle\Traits\Entity;

use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Entity\Company;

trait CompanyAware
{
    /**
     * @ORM\ManyToOne(targetEntity=Company::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private Company $company;

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;
        return $this;
    }
}
