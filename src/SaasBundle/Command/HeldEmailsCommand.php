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

namespace SolidInvoice\SaasBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use SolidInvoice\SaasBundle\Entity\HeldEmail;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Uid\Ulid;
use function array_keys;
use function array_map;
use function count;
use function implode;
use function sprintf;

/**
 * Review the client emails HoldNewAccountClientEmailListener held back.
 *
 *   list               what is waiting
 *   release <id>       send it, exactly as it was held
 *   reject <id>        drop every email the company has held, and disable the company
 */
#[AsCommand(
    name: 'solidinvoice:saas:held-emails',
    description: 'List, release or reject client emails held back from new accounts',
)]
final class HeldEmailsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::OPTIONAL, 'list, release or reject', 'list')
            ->addArgument('id', InputArgument::OPTIONAL, 'The held email, for release and reject');
    }

    protected function handle(): int
    {
        // Held emails span companies.
        $filters = $this->entityManager->getFilters();
        $companyFilterEnabled = $filters->isEnabled('company');

        if ($companyFilterEnabled) {
            $filters->suspend('company');
        }

        try {
            return match ($this->io->getArgument('action')) {
                'list' => $this->list(),
                'release' => $this->release(),
                'reject' => $this->reject(),
                default => $this->fail('Action must be list, release or reject.'),
            };
        } finally {
            if ($companyFilterEnabled) {
                $filters->restore('company');
            }
        }
    }

    private function list(): int
    {
        $held = $this->entityManager->getRepository(HeldEmail::class)->findBy([], ['created' => 'ASC']);

        $this->io->table(
            ['Id', 'Held', 'Company', 'Subject', 'To', 'Signals'],
            array_map(static fn (HeldEmail $email): array => [
                $email->getId()->toBase58(),
                $email->getCreated()->format('Y-m-d H:i'),
                $email->getCompany()->getName(),
                $email->getSubject(),
                implode(', ', $email->getRecipients()),
                implode(', ', array_map(static fn (string $signal, int $points): string => "{$signal} ({$points})", array_keys($email->getReasons()), $email->getReasons())),
            ], $held),
        );

        return self::SUCCESS;
    }

    private function release(): int
    {
        $held = $this->find();

        if (! $held instanceof HeldEmail) {
            return $this->fail('No held email with that id.');
        }

        // A RawMessage is not an Email, so no mail listener touches it again, the hold included.
        $this->mailer->send(
            new RawMessage($held->getMessage()),
            new Envelope(Address::create($held->getSender()), array_map(Address::create(...), $held->getRecipients())),
        );

        $this->entityManager->remove($held);
        $this->entityManager->flush();

        $this->io->success(sprintf('Sent "%s" to %s.', $held->getSubject(), implode(', ', $held->getRecipients())));

        return self::SUCCESS;
    }

    private function reject(): int
    {
        $held = $this->find();

        if (! $held instanceof HeldEmail) {
            return $this->fail('No held email with that id.');
        }

        $company = $held->getCompany();
        $all = $this->entityManager->getRepository(HeldEmail::class)->findBy(['company' => $company]);

        foreach ($all as $email) {
            $this->entityManager->remove($email);
        }

        $company->disable();
        $this->entityManager->flush();

        $this->io->success(sprintf('Dropped %d held email(s) and disabled %s.', count($all), $company->getName()));

        return self::SUCCESS;
    }

    private function find(): ?HeldEmail
    {
        try {
            $id = Ulid::fromString((string) $this->io->getArgument('id'));
        } catch (InvalidArgumentException) {
            return null;
        }

        return $this->entityManager->find(HeldEmail::class, $id);
    }

    private function fail(string $message): int
    {
        $this->io->error($message);

        return self::FAILURE;
    }
}
