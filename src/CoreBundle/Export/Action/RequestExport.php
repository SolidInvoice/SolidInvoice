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

use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\ExportJob;
use SolidInvoice\CoreBundle\Export\Enum\ExportFormat;
use SolidInvoice\CoreBundle\Export\Message\RequestCompanyExport;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\CoreBundle\Repository\ExportJobRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Ulid;
use ValueError;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final readonly class RequestExport
{
    public const string CSRF_TOKEN_ID = 'export.request';

    public function __construct(
        private ExportJobRepository $exportJobRepository,
        private CompanyRepository $companyRepository,
        private CompanySelector $companySelector,
        private MessageBusInterface $messageBus,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request, ?UserInterface $user): RedirectResponse
    {
        if (! $user instanceof User) {
            throw new AccessDeniedException();
        }

        $token = (string) $request->request->get('_token');
        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $token))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $format = $this->resolveFormat((string) $request->request->get('format', ''));

        $activeCompanyId = $this->companySelector->getCompany();
        if (! $activeCompanyId instanceof Ulid) {
            throw new BadRequestHttpException('No active company context.');
        }

        $company = $this->companyRepository->find($activeCompanyId);
        if ($company === null) {
            throw new BadRequestHttpException('Active company not found.');
        }

        $job = new ExportJob($user->getId(), $format)->setCompany($company);
        $this->exportJobRepository->save($job);

        $this->messageBus->dispatch(new RequestCompanyExport(
            exportJobId: $job->getId(),
            companyId: $activeCompanyId,
            userId: $user->getId(),
        ));

        return new RedirectResponse($this->urlGenerator->generate('_export_list'));
    }

    private function resolveFormat(string $raw): ExportFormat
    {
        try {
            return ExportFormat::from($raw);
        } catch (ValueError) {
            throw new BadRequestHttpException(sprintf('Unsupported export format "%s".', $raw));
        }
    }
}
