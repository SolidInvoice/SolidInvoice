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
        self::bootKernel();

        $_SERVER['locale'] = $_ENV['locale'] = 'en_US';
        $_SERVER['installed'] = $_ENV['installed'] = date(DateTimeInterface::ATOM);

        $doctrine = static::getContainer()->get('doctrine');
        $em = $doctrine->getManager();

        $company = $doctrine
            ->getRepository(Company::class)
            ->findOneBy([]);

        $loadDefaultData = false;

        if (! $company instanceof Company) {
            $company = new Company();
            $company->setName('SolidInvoice');
            $em->persist($company);
            $em->flush();
            $loadDefaultData = true;
        }

        $this->company = $company;

        // @phpstan-ignore-next-line Ignore this line in PHPStan, since it sees the CompanySelector service as private
        static::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        if ($loadDefaultData) {
            static::getContainer()->get(DefaultData::class)($company, ['currency' => 'USD']);
        }
    }

    /**
     * @after
     */
    public function clearEnv(): void
    {
        unset($_SERVER['locale'], $_ENV['locale'], $_SERVER['installed'], $_ENV['installed']);
    }
}
