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
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Test\Traits\SymfonyKernelTrait;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;
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
        if (Configuration::isBooted() && ! Configuration::instance()->isPersistenceAvailable()) {
            Configuration::boot(static function () {
                return static::getContainer()->get('.zenstruck_foundry.configuration'); // @phpstan-ignore-line
            });
        }

        ResetDatabaseManager::resetBeforeEachTest(
            static fn () => static::bootKernel(),
            static fn () => static::ensureKernelShutdown(),
        );

        $_SERVER['SOLIDINVOICE_LOCALE'] = $_ENV['SOLIDINVOICE_LOCALE'] = 'en_US';
        $_SERVER['SOLIDINVOICE_INSTALLED'] = $_ENV['SOLIDINVOICE_INSTALLED'] = date(DateTimeInterface::ATOM);

        /** @var ManagerRegistry $registry */
        $registry = static::getContainer()->get('doctrine');

        $this->company = new Company();
        $this->company->setName('SolidInvoice');
        $this->company->currency = 'USD';
        $registry->getManager()->persist($this->company);
        $registry->getManager()->flush();

        static::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());
    }

    /**
     * @after
     */
    public function resetInstallation(): void
    {
        unset(
            $_SERVER['SOLIDINVOICE_LOCALE'],
            $_ENV['SOLIDINVOICE_LOCALE'],
            $_SERVER['SOLIDINVOICE_INSTALLED'],
            $_ENV['SOLIDINVOICE_INSTALLED'],
            $this->company,
        );
    }

    /**
     * @internal
     * @beforeClass
     */
    public static function _resetDatabaseBeforeFirstTest(): void
    {
        ResetDatabaseManager::resetBeforeFirstTest(
            static fn () => static::bootKernel(),
            static fn () => static::ensureKernelShutdown(),
        );
    }
}
