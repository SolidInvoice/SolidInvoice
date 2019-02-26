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

namespace SolidInvoice\UserBundle\Form\Handler;

use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Form\Type\ApiTokenType;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerOptionsResolver;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\FormHandler\Options;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApiFormHandler implements FormHandlerInterface, FormHandlerResponseInterface, FormHandlerSuccessInterface, FormHandlerOptionsResolver
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
    public function getForm(FormFactoryInterface $factory = null, Options $options)
    {
        return $factory->create(ApiTokenType::class, $options->get('api_token'));
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(FormRequest $formRequest)
    {
        return new Template(
            '@SolidInvoiceUser/Api/create.html.twig',
            [
                'form' => $formRequest->getForm()->createView(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess($user, FormRequest $form): ?Response
    {
        /* @var ApiToken $user */
        $user->setToken($this->tokenManager->generateToken());

        $this->save($user);

        return new JsonResponse(
            [
                'token' => $user->getToken(),
                'name' => $user->getName(),
                'id' => $user->getId(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('api_token')
            ->setAllowedTypes('api_token', ApiToken::class);
    }
}
