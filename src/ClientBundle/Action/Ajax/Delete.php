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

namespace SolidInvoice\ClientBundle\Action\Ajax;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

final class Delete implements AjaxResponse
{
    use JsonTrait;

    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function __invoke(Client $client, Session $session): Response
    {
        $this->clientRepository->delete($client);

        $session->getFlashBag()->add('success', $this->translator->trans('client.delete_success'));

        return $this->json(['status' => 'success']);
    }
}
