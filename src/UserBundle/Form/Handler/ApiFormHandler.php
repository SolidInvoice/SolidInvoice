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

/**
 * @see \SolidInvoice\UserBundle\Tests\Form\Handler\ApiFormHandlerTest
 */
class ApiFormHandler implements FormHandlerInterface, FormHandlerResponseInterface, FormHandlerSuccessInterface, FormHandlerOptionsResolver
{
    use SaveableTrait;

    private ApiTokenManager $tokenManager;

    public function __construct(ApiTokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    public function getForm(FormFactoryInterface $factory, Options $options)
    {
        return $factory->create(ApiTokenType::class, $options->get('api_token'));
    }

    public function getResponse(FormRequest $formRequest)
    {
        return new Template(
            '@SolidInvoiceUser/Api/create.html.twig',
            [
                'form' => $formRequest->getForm()->createView(),
            ]
        );
    }

    public function onSuccess(FormRequest $form, $data): ?Response
    {
        /** @var ApiToken $data */
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('api_token')
            ->setAllowedTypes('api_token', ApiToken::class);
    }
}
