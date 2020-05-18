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

namespace SolidInvoice\ClientBundle\Action\Ajax;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class Delete implements AjaxResponse
{
    use JsonTrait;

    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ClientRepository $clientRepository, SessionInterface $session, TranslatorInterface $translator)
    {
        $this->clientRepository = $clientRepository;
        $this->session = $session;
        $this->translator = $translator;
    }

    public function __invoke(Client $client): Response
    {
        $this->clientRepository->delete($client);

        $this->session->getFlashBag()->add('success', $this->translator->trans('client.delete_success'));

        return $this->json(['status' => 'success']);
    }
}
