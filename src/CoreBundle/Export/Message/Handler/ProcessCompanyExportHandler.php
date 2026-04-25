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

namespace SolidInvoice\CoreBundle\Export\Message\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\ExportJob;
use SolidInvoice\CoreBundle\Export\CompanyExporter;
use SolidInvoice\CoreBundle\Export\Email\ExportReadyEmail;
use SolidInvoice\CoreBundle\Export\Enum\ExportStatus;
use SolidInvoice\CoreBundle\Export\Message\RequestCompanyExport;
use SolidInvoice\CoreBundle\Repository\ExportJobRepository;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Ulid;
use Throwable;

/**
 * Handles the async export pipeline: load job → switch company → export → email.
 *
 * Idempotency: re-delivery skips any job whose status is no longer Pending. This means
 * a Failed job will NOT be retried by Messenger; the user must request a new export.
 *
 * TODO(stuck-processing-recovery): if a worker is hard-killed (OOM, SIGKILL) AFTER
 *   markProcessing() flushed but BEFORE the catch block runs, the job stays in
 *   Processing forever and the idempotency guard prevents recovery. Add a CronBundle
 *   command that finds jobs in Processing older than ~30 minutes, marks them Failed
 *   with reason "worker timed out" so the user can request a fresh export.
 */
#[AsMessageHandler]
final readonly class ProcessCompanyExportHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ExportJobRepository $exportJobRepository,
        private UserRepository $userRepository,
        private CompanyExporter $companyExporter,
        private CompanySelector $companySelector,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
        private string $projectDir,
    ) {
    }

    public function __invoke(RequestCompanyExport $message): void
    {
        $job = $this->exportJobRepository->find($message->exportJobId);

        if (! $job instanceof ExportJob) {
            $this->logger->warning('Export job not found, skipping', [
                'export_job_id' => $message->exportJobId->toString(),
            ]);
            return;
        }

        // Idempotency: on Messenger retry, a job that already failed or completed must not be re-run.
        if ($job->getStatus() !== ExportStatus::Pending) {
            $this->logger->info('Export job is no longer pending, skipping', [
                'export_job_id' => $job->getId()->toString(),
                'current_status' => $job->getStatus()->value,
            ]);
            return;
        }

        $this->companySelector->switchCompany($message->companyId);

        try {
            $job->markProcessing();
            $this->entityManager->flush();

            $relativePath = $this->companyExporter->export($job);

            $absolutePath = $this->projectDir . '/' . $relativePath;
            $fileSize = @filesize($absolutePath);

            $job->markCompleted($relativePath, $fileSize === false ? 0 : $fileSize);
            $this->entityManager->flush();

            $this->sendEmail($job, $message->userId);
        } catch (Throwable $e) {
            $this->logger->error('Export job failed', [
                'export_job_id' => $job->getId()->toString(),
                'exception' => $e,
            ]);

            $job->markFailed(sprintf('%s: %s', $e::class, $e->getMessage()));
            $this->entityManager->flush();

            throw $e;
        } finally {
            $this->companySelector->reset();
        }
    }

    private function sendEmail(ExportJob $job, Ulid $userId): void
    {
        $user = $this->userRepository->find($userId);
        if (! $user instanceof User) {
            $this->logger->warning('Export requester not found, skipping email', [
                'export_job_id' => $job->getId()->toString(),
                'user_id' => $userId->toString(),
            ]);
            return;
        }

        $downloadUrl = $this->urlGenerator->generate(
            '_export_download',
            ['id' => $job->getId()->toBase58()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $email = (new ExportReadyEmail($job, $user, $downloadUrl))
            ->to($user->getEmail());

        $this->mailer->send($email);
    }
}
