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

namespace SolidInvoice\InstallBundle\Test;

use DateTimeInterface;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Company\DefaultData;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Test\Traits\SymfonyKernelTrait;
use function date;

trait EnsureApplicationInstalled
{
    use SymfonyKernelTrait;

    protected Company $company;

    /**
     * @before
     */
    public function installApplication(): void
    {
        if (! static::$booted) {
            static::bootKernel();
        }

        $_SERVER['SOLIDINVOICE_LOCALE'] = $_ENV['SOLIDINVOICE_LOCALE'] = 'en_US';
        $_SERVER['SOLIDINVOICE_INSTALLED'] = $_ENV['SOLIDINVOICE_INSTALLED'] = date(DateTimeInterface::ATOM);

        /** @var ManagerRegistry $registry */
        $registry = static::getContainer()->get('doctrine');
        $company = $registry
            ->getRepository(Company::class)
            ->findOneBy([]);

        if (! $company instanceof Company) {
            $this->company = new Company();
            $this->company->setName('SolidInvoice');
            $registry->getManager()->persist($this->company);
            $registry->getManager()->flush();

            static::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

            /** @var DefaultData $defaultData */
            $defaultData = static::getContainer()->get(DefaultData::class);
            $defaultData($this->company, ['currency' => 'USD']);
        } else {
            $this->company = $company;

            static::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());
        }

    }

    /**
     * @after
     */
    public function clearEnv(): void
    {
        unset($_SERVER['SOLIDINVOICE_LOCALE'], $_ENV['SOLIDINVOICE_LOCALE'], $_SERVER['SOLIDINVOICE_INSTALLED'], $_ENV['SOLIDINVOICE_INSTALLED']);
    }
}
