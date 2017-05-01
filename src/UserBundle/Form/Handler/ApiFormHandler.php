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

namespace CSBill\UserBundle\Form\Handler;

use CSBill\ApiBundle\ApiTokenManager;
use CSBill\CoreBundle\Templating\Template;
use CSBill\CoreBundle\Traits\SaveableTrait;
use CSBill\UserBundle\Entity\ApiToken;
use CSBill\UserBundle\Form\Type\ApiTokenType;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiFormHandler implements FormHandlerInterface, FormHandlerResponseInterface, FormHandlerSuccessInterface
{
    use SaveableTrait;

    /**
     * @var ApiTokenManager
     */
    private $tokenManager;

    public function __construct(ApiTokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(FormFactoryInterface $factory = null, ...$options)
    {
        return $factory->create(ApiTokenType::class, $options[0] ?? new ApiToken());
    }

    /**
     * {@inheritdoc]
     */
    public function getResponse(FormRequest $formRequest)
    {
        return new Template(
            'CSBillUserBundle:Api:create.html.twig',
            [
                'form' => $formRequest->getForm()->createView(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess($data, FormRequest $form): ?Response
    {
        /* @var ApiToken $data */
        $data->setToken($this->tokenManager->generateToken());

        $this->save($data);

        return new JsonResponse(
            [
                'token' => $data->getToken(),
                'name' => $data->getName(),
                'id' => $data->getId(),
            ]
        );
    }
}
