<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Action\Grid;

use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use SolidInvoice\UserBundle\Manager\UserManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

final class Delete implements AjaxResponse
{
    use JsonTrait;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(UserManager $userManager, TokenStorageInterface $tokenStorage)
    {
        $this->userManager = $userManager;
        $this->tokenStorage = $tokenStorage;
    }

    public function __invoke(Request $request)
    {
        $users = $request->request->get('data');


        $token = $this->tokenStorage->getToken();
        $currentUser = $token->getUser();

        if (in_array($currentUser->getId(), array_map('intval', $users), true)) {
            return $this->json(['message' => "You can't delete the current logged in user"]);
        }

        return $this->json($this->userManager->deleteUsers($users));
    }
}
