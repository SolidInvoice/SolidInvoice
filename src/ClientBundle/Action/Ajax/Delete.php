<?php

namespace CSBill\ClientBundle\Action\Ajax;

use CSBill\ClientBundle\Entity\Client;
use CSBill\CoreBundle\Traits\JsonTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

class Delete
{
    use JsonTrait;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(RegistryInterface $doctrine, Session $session, TranslatorInterface $translator)
    {
        $this->doctrine = $doctrine;
        $this->session = $session;
        $this->translator = $translator;
    }

    public function __invoke(Client $client): Response
    {
        $em = $this->doctrine->getManager();
        $em->remove($client);
        $em->flush();

        $this->session->getFlashBag()->add('success', $this->translator->trans('client.delete_success'));

        return $this->json(['status' => 'success']);
    }
}