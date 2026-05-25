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
use Doctrine\Persistence\ManagerRegistry;
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
 * @see \SolidInvoice\CoreBundle\Tests\Export\Message\Handler\ProcessCompanyExportHandlerTest
 */
#[AsMessageHandler]
final readonly class ProcessCompanyExportHandler
{
    /**
     * Generic user-facing failure message. The full exception (class, message, trace)
     * is captured via the logger; only this safe label is persisted on the export job
     * so the failure reason rendered in /profile/exports never leaks DB error text,
     * file paths, or other internals.
     */
    private const string FAILURE_REASON_USER_MESSAGE = 'Export failed. Please try again or contact support if the problem persists.';

    public function __construct(
        private ManagerRegistry $registry,
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
            // CompanyExporter::zipDirectory throws if the archive could not be written,
            // so reaching this point means the file exists. filesize() may still return
            // false if the file is unreadable for unrelated reasons — fall back to 0
            // rather than swallowing the underlying I/O error silently.
            $fileSize = is_file($absolutePath) ? filesize($absolutePath) : false;

            $job->markCompleted($relativePath, $fileSize === false ? 0 : $fileSize);
            $this->entityManager->flush();

            $this->sendEmail($job, $message->userId);
        } catch (Throwable $e) {
            $this->logger->error('Export job failed', [
                'export_job_id' => $job->getId()->toString(),
                'exception' => $e,
            ]);

            $this->persistFailure($job);

            throw $e;
        } finally {
            $this->companySelector->reset();
        }
    }

    /**
     * Persists the Failed status, reopening the EntityManager if Doctrine closed it
     * during the original failure (constraint violation, lost connection, etc).
     * Without this, the failure write would itself throw EntityManagerClosed and the
     * job would be silently stuck in Processing.
     *
     * Note: after `resetManager()` the constructor-injected `$this->entityManager` is
     * the stale (closed) reference. This handler does no further flushes after the
     * catch block, so the stale reference is never reused. Any future code that
     * touches the EM after this point must obtain a fresh manager via the registry.
     */
    private function persistFailure(ExportJob $job): void
    {
        if (! $this->entityManager->isOpen()) {
            $this->registry->resetManager();
        }

        $manager = $this->registry->getManager();
        assert($manager instanceof EntityManagerInterface);

        $tracked = $manager->find(ExportJob::class, $job->getId()) ?? $job;
        $tracked->markFailed(self::FAILURE_REASON_USER_MESSAGE);

        $manager->persist($tracked);
        $manager->flush();
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

        $email = new ExportReadyEmail($job, $user, $downloadUrl)
            ->to($user->getEmail());

        $this->mailer->send($email);
    }
}
