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
use CSBill\CoreBundle\Traits\SaveableTrait;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerOptionsResolver;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\FormHandler\Options;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractClientFormHandler implements FormHandlerInterface, FormHandlerSuccessInterface, FormHandlerResponseInterface, FormHandlerOptionsResolver
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
    public function getForm(FormFactoryInterface $factory = null, Options $options): FormInterface
    {
        return $factory->create(ClientType::class, $options->get('client', new Client()));
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess($client, FormRequest $form): Response
    {
        /** @var Client $client */
        if (!$client->getStatus()) {
            $client->setStatus(Status::STATUS_ACTIVE);
        }

        $this->save($client);

        $route = $this->router->generate('_clients_view', ['id' => $client->getId()]);

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): iterable
            {
                yield self::FLASH_SUCCESS => 'client.create.success';
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('client')
            ->setAllowedTypes('client', ['null', Client::class]);
    }
}
