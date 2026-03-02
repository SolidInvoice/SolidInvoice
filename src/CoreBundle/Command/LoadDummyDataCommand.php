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

namespace SolidInvoice\CoreBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\DummyData\DummyDataLoader;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Uid\Ulid;
use function array_combine;
use function array_map;
use function assert;
use function sprintf;

#[AsCommand(
    name: 'solidinvoice:dummy-data:load',
    description: 'Load dummy data into a company for demonstration purposes',
)]
final class LoadDummyDataCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly CompanySelector $companySelector,
        private readonly DummyDataLoader $dummyDataLoader,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'company-id',
            null,
            InputOption::VALUE_OPTIONAL,
            'The ULID of the company to load dummy data into',
        );
    }

    protected function handle(): int
    {
        $em = $this->registry->getManager();
        assert($em instanceof EntityManagerInterface);

        $filters = $em->getFilters();
        $companyFilterEnabled = $filters->isEnabled('company');

        if ($companyFilterEnabled) {
            $filters->disable('company');
        }

        try {
            $company = $this->resolveCompany($em);

            if (! $company instanceof Company) {
                $this->io->error('No company found.');

                return self::FAILURE;
            }

            $this->companySelector->switchCompany($company->getId());

            $this->io->writeln(sprintf('Loading dummy data for company: <info>%s</info>', $company->getName()));

            $this->dummyDataLoader->load($company);

            $this->io->success('Dummy data loaded successfully');
        } finally {
            $this->companySelector->reset();

            if ($companyFilterEnabled) {
                $filters->enable('company');
            }
        }

        return self::SUCCESS;
    }

    private function resolveCompany(EntityManagerInterface $em): ?Company
    {
        $companyIdOption = $this->io->getOption('company-id');

        if (null !== $companyIdOption) {
            if (! Ulid::isValid((string) $companyIdOption)) {
                $this->io->error('The provided company ID is not a valid ULID.');

                return null;
            }

            return $em->getRepository(Company::class)->find(Ulid::fromString((string) $companyIdOption));
        }

        /** @var CompanyRepository $companyRepository */
        $companyRepository = $em->getRepository(Company::class);

        /** @var Company[] $companies */
        $companies = $companyRepository->findBy([], ['name' => 'ASC']);

        if ([] === $companies) {
            return null;
        }

        if (1 === count($companies)) {
            return $companies[0];
        }

        $labels = array_map(
            static fn (Company $company): string => sprintf('%s (%s)', $company->getName(), $company->getId()->toString()),
            $companies,
        );

        $companyMap = array_combine($labels, $companies);

        $chosen = $this->io->choice('Select a company to load dummy data into', $labels);

        return $companyMap[$chosen];
    }
}
