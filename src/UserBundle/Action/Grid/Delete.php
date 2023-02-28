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
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidInvoice\UserBundle\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

final class Delete implements AjaxResponse
{
    use JsonTrait;

    private Security $security;

    /**
     * @var UserRepository|UserRepositoryInterface
     */
    private $userRepository;

    private CompanySelector $companySelector;

    private CompanyRepository $companyRepository;

    public function __construct(
        UserRepositoryInterface $userRepository,
        CompanyRepository $companyRepository,
        Security $security,
        CompanySelector $companySelector
    ) {
        $this->security = $security;
        $this->userRepository = $userRepository;
        $this->companySelector = $companySelector;
        $this->companyRepository = $companyRepository;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $users = $request->request->all('data');

        $currentUser = $this->security->getUser();

        assert($currentUser instanceof User);

        if (in_array($currentUser->getId(), array_map('intval', $users), true)) {
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
