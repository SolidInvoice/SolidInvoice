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

use SolidInvoice\CoreBundle\Entity\ExportJob;
use SolidInvoice\CoreBundle\Export\Enum\ExportFormat;
use SolidInvoice\CoreBundle\Repository\ExportJobRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final readonly class ListExports
{
    public function __construct(
        private ExportJobRepository $exportJobRepository,
    ) {
    }

    /**
     * @return array{jobs: list<ExportJob>, formats: list<ExportFormat>}
     */
    #[Template('@SolidInvoiceCore/Export/list.html.twig')]
    public function __invoke(?UserInterface $user): array
    {
        if (! $user instanceof User) {
            throw new AccessDeniedException();
        }

        return [
            'jobs' => $this->exportJobRepository->findForUser($user->getId()),
            'formats' => ExportFormat::cases(),
        ];
    }
}
