<?php
declare(strict_types=1);

namespace SolidInvoice\UserBundle\Action;

use Generator;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

final class AcceptInvitation
{
    private UserInvitationRepository $repository;
    private UserRepository $userRepository;
    private RouterInterface $router;

    public function __construct(
        UserInvitationRepository $repository,
        UserRepository $userRepository,
        RouterInterface $router
    ) {
        $this->repository = $repository;
        $this->userRepository = $userRepository;
        $this->router = $router;
    }

    public function __invoke(string $id): RedirectResponse
    {
        $invitation = $this->repository->find($id);

        if (null === $invitation) {
            throw new NotFoundHttpException('Invitation is not valid');
        }

        $existingUser = $this->userRepository->findOneBy(['email' => $invitation->getEmail()]);

        if ($existingUser instanceof User) {
            $existingUser->addCompany($invitation->getCompany());
            $this->userRepository->save($existingUser);

            $this->repository->delete($invitation);

            $route = $this->router->generate('_login');

            return new class($route) extends RedirectResponse implements FlashResponse {
                public function getFlash(): Generator
                {
                    yield FlashResponse::FLASH_SUCCESS => 'users.invitation.accept.success';
                }
            };
        }

        $route = $this->router->generate('_register', ['invitation' => $invitation->getId()]);

        return new RedirectResponse($route);
    }
}
