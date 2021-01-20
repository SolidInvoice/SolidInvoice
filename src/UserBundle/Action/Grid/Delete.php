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

use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

final class Delete implements AjaxResponse
{
    use JsonTrait;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    public function __construct(UserRepositoryInterface $userRepository, Security $security)
    {
        $this->security = $security;
        $this->userRepository = $userRepository;
    }

    public function __invoke(Request $request)
    {
        $users = $request->request->get('data');

        $currentUser = $this->security->getUser();

        assert($currentUser instanceof User);

        if (in_array($currentUser->getId(), array_map('intval', $users), true)) {
            return $this->json(['message' => "You can't delete the current logged in user"]);
        }

        return $this->json($this->userRepository->deleteUsers($users));
    }
}
