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
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Test\Traits\SymfonyKernelTrait;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\ContextProvider\SourceContextProvider;
use Symfony\Component\VarDumper\Dumper\ContextualizedDumper;
use Symfony\Component\VarDumper\VarDumper;
use function date;
use function get_debug_type;

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

        $this->company = static::getContainer()->get('doctrine')
            ->getRepository(Company::class)
            ->findOneBy([]);

        // @phpstan-ignore-next-line Ignore this line in PHPStan, since it sees the CompanySelector service as private
        $companySelector = static::getContainer()->get(CompanySelector::class);


        $cloner = new VarCloner();
        $cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);
        $dumper = new CliDumper();

        VarDumper::setHandler(function ($var) use ($cloner, $dumper) {
            var_dump($dumper->dump($cloner->cloneVar($var), true));
        });


        VarDumper::dump(static::getContainer()->get(\SolidInvoice\SettingsBundle\SystemConfig::class));

        VarDumper::dump($companySelector);

        $companySelector->switchCompany($this->company->getId());

        $_SERVER['locale'] = $_ENV['locale'] = 'en_US';
        $_SERVER['installed'] = $_ENV['installed'] = date(DateTimeInterface::ATOM);
    }

    /**
     * @after
     */
    public function clearEnv(): void
    {
        unset($_SERVER['locale'], $_ENV['locale'], $_SERVER['installed'], $_ENV['installed']);
    }
}
