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

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:custom-fields:check-orphans', description: 'Find (and optionally clean) custom_field_value rows whose target record is gone.')]
final class CustomFieldOrphanCheckCommand extends Command
{
    public function __construct(
        private readonly Connection $connection
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('clean', null, InputOption::VALUE_NONE, 'Delete orphan rows after listing them.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $orphans = $this->connection->fetchAllAssociative(
            "SELECT v.id, v.target, v.target_id FROM custom_field_value v
             LEFT JOIN clients c ON v.target = 'CLIENT' AND v.target_id = c.id
             LEFT JOIN contacts ct ON v.target = 'CONTACT' AND v.target_id = ct.id
             WHERE (v.target = 'CLIENT' AND c.id IS NULL)
                OR (v.target = 'CONTACT' AND ct.id IS NULL)"
        );

        if ($orphans === []) {
            $io->success('No orphan custom field values.');
            return Command::SUCCESS;
        }

        $io->table(['id', 'target', 'target_id'], $orphans);

        if ($input->getOption('clean')) {
            $this->connection->executeStatement(
                "DELETE FROM custom_field_value
                 WHERE id IN (
                   SELECT id FROM custom_field_value v
                   LEFT JOIN clients c ON v.target = 'CLIENT' AND v.target_id = c.id
                   LEFT JOIN contacts ct ON v.target = 'CONTACT' AND v.target_id = ct.id
                   WHERE (v.target = 'CLIENT' AND c.id IS NULL)
                      OR (v.target = 'CONTACT' AND ct.id IS NULL)
                 )"
            );
            $io->success('Cleaned ' . count($orphans) . ' orphan rows.');
        } else {
            $io->warning('Re-run with --clean to delete these.');
        }

        return Command::SUCCESS;
    }
}
