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

namespace SolidInvoice\UserBundle\Action\Grid;

use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class Delete implements AjaxResponse
{
    use JsonTrait;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly Security $security,
        private readonly CompanySelector $companySelector
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $users = $request->request->all('data');

        $currentUser = $this->security->getUser();

        assert($currentUser instanceof User);

        if (in_array($currentUser->getId()->toString(), $users, true)) {
            return $this->json(['message' => "You can't delete the current logged in user"]);
        }

        $company = $this->companyRepository->find($this->companySelector->getCompany());

        if (! $company instanceof Company) {
            return $this->json(['message' => 'Company not found']);
        }

        foreach ($users as $userId) {
            $user = $this->userRepository->find($userId);

            if ($user instanceof User) {
                $user->removeCompany($company);
            }

            $this->userRepository->save($user);
        }

        return $this->json();
    }
}
