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

namespace SolidInvoice\ClientBundle\Action;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class Delete
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly TranslatorInterface $translator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly RouterInterface $router
    ) {
    }

    public function __invoke(Client $client, Request $request, Session $session): Response
    {
        $token = $request->request->get('_token');

        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken('delete' . $client->getId(), $token))) {
            $session->getFlashBag()->add('danger', $this->translator->trans('Invalid CSRF token'));

            return new RedirectResponse($this->router->generate('_clients_view', ['id' => $client->getId()]));
        }

        $this->clientRepository->delete($client);

        $session->getFlashBag()->add('success', $this->translator->trans('client.delete_success'));

        return new RedirectResponse($this->router->generate('_clients_index'));
    }
}
