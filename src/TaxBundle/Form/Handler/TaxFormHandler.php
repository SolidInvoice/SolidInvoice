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

namespace SolidInvoice\TaxBundle\Form\Handler;

use Generator;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Form\Type\TaxType;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerOptionsResolver;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\FormHandler\Options;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

/**
 * @see \SolidInvoice\TaxBundle\Tests\Form\Handler\TaxFormHandlerTest
 */
class TaxFormHandler implements FormHandlerInterface, FormHandlerSuccessInterface, FormHandlerResponseInterface, FormHandlerOptionsResolver
{
    use SaveableTrait;

    public function __construct(private readonly RouterInterface $router)
    {
    }

    public function getForm(FormFactoryInterface $factory, Options $options)
    {
        return $factory->create(TaxType::class, $options->get('tax', new Tax()));
    }

    public function onSuccess(FormRequest $form, $data): ?Response
    {
        $this->save($data);

        $route = $this->router->generate('_tax_rates');

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield FlashResponse::FLASH_SUCCESS => 'Tax rate successfully saved';
            }
        };
    }

    public function getResponse(FormRequest $formRequest)
    {
        return new Template('@SolidInvoiceTax/Default/form.html.twig', ['form' => $formRequest->getForm()->createView()]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('tax')
            ->setAllowedTypes('tax', Tax::class);
    }
}
