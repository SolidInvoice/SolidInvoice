<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Form\Handler;

use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Form\Type\ClientType;
use CSBill\ClientBundle\Model\Status;
use CSBill\CoreBundle\Response\FlashResponse;
use CSBill\CoreBundle\Templating\Template;
use CSBill\CoreBundle\Traits\SaveableTrait;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class ClientFormHandler implements FormHandlerInterface, FormHandlerSuccessInterface, FormHandlerResponseInterface
{
    use SaveableTrait;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(FormFactoryInterface $factory = null, ...$options): FormInterface
    {
        return $factory->create(ClientType::class, $options[0] ?? new Client());
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess($data, FormRequest $form): Response
    {
        /** @var Client $data */
        if (!$data->getStatus()) {
            $data->setStatus(Status::STATUS_ACTIVE);
        }

        $this->save($data);

        $route = $this->router->generate('_clients_view', ['id' => $data->getId() ?? 5]);

        return new class($route) extends RedirectResponse implements FlashResponse
        {
            public function getFlash(): iterable
            {
                yield self::FLASH_SUCCESS => 'client.create.success';
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(FormRequest $formRequest): Template
    {
        return new Template('@CSBillClient/Default/add.html.twig', ['form' => $formRequest->getForm()->createView()]);
    }
}