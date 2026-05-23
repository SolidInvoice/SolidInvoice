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

namespace SolidInvoice\CoreBundle\Export\Action;

use InvalidArgumentException;
use SolidInvoice\CoreBundle\Entity\ExportJob;
use SolidInvoice\CoreBundle\Export\Enum\ExportStatus;
use SolidInvoice\CoreBundle\Export\Security\Voter\ExportJobVoter;
use SolidInvoice\CoreBundle\Repository\ExportJobRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Ulid;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final readonly class DownloadExport
{
    public function __construct(
        private ExportJobRepository $exportJobRepository,
        private AuthorizationCheckerInterface $authorizationChecker,
        private UrlGeneratorInterface $urlGenerator,
        private string $projectDir,
    ) {
    }

    public function __invoke(string $id): BinaryFileResponse|RedirectResponse
    {
        $ulid = $this->parseUlid($id);

        $job = $this->exportJobRepository->find($ulid);
        if (! $job instanceof ExportJob) {
            throw new NotFoundHttpException();
        }

        if (! $this->authorizationChecker->isGranted(ExportJobVoter::DOWNLOAD, $job)) {
            throw new AccessDeniedException();
        }

        if ($job->getStatus() !== ExportStatus::Completed) {
            return new RedirectResponse($this->urlGenerator->generate('_export_list'));
        }

        $absolutePath = $job->resolveAbsolutePath($this->projectDir);
        if ($absolutePath === null || ! is_file($absolutePath)) {
            throw new NotFoundHttpException('Export archive is missing on disk.');
        }

        // Defense in depth: reject any archive_path that resolves outside the project's
        // var/exports directory. archive_path is always written by CompanyExporter from
        // ULIDs, but we never want a malformed value to enable arbitrary file reads.
        $exportRoot = realpath($this->projectDir . '/var/exports');
        $resolved = realpath($absolutePath);
        if ($exportRoot === false || $resolved === false || ! str_starts_with($resolved, $exportRoot . DIRECTORY_SEPARATOR)) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($absolutePath);
        $response->setContentDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            sprintf('solidinvoice-export-%s.zip', $job->getCreatedAt()->format('Y-m-d')),
        );
        $response->headers->set('Content-Type', 'application/zip');

        return $response;
    }

    private function parseUlid(string $id): Ulid
    {
        try {
            return Ulid::fromString($id);
        } catch (InvalidArgumentException) {
            throw new NotFoundHttpException();
        }
    }
}
