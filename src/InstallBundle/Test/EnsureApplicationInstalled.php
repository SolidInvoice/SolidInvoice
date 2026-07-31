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
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use function date;
use function putenv;

trait EnsureApplicationInstalled
{
    protected Company $company;

    #[Before]
    public function createCompany(): void
    {
        $_SERVER['SOLIDINVOICE_LOCALE'] = $_ENV['SOLIDINVOICE_LOCALE'] = 'en_US';
        $_SERVER['SOLIDINVOICE_INSTALLED'] = $_ENV['SOLIDINVOICE_INSTALLED'] = date(DateTimeInterface::ATOM);
        putenv('SOLIDINVOICE_INSTALLED=' . $_SERVER['SOLIDINVOICE_INSTALLED']);

        $this->company = CompanyFactory::createOne(['name' => SolidInvoiceCoreBundle::APP_NAME]);

        static::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());
    }

    #[After]
    public function resetCompany(): void
    {
        unset(
            $_SERVER['SOLIDINVOICE_LOCALE'],
            $_ENV['SOLIDINVOICE_LOCALE'],
            $_SERVER['SOLIDINVOICE_INSTALLED'],
            $_ENV['SOLIDINVOICE_INSTALLED'],
            $this->company
        );
        putenv('SOLIDINVOICE_INSTALLED=');
    }
}
