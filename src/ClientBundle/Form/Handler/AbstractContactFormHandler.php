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

namespace SolidInvoice\ClientBundle\Form\Handler;

use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Form\Type\ContactType;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\CoreBundle\Traits\SerializeTrait;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerOptionsResolver;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\FormHandler\Options;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractContactFormHandler implements FormHandlerInterface, FormHandlerResponseInterface, FormHandlerSuccessInterface, FormHandlerOptionsResolver
{
    use SaveableTrait;
    use SerializeTrait;

    public function getResponse(FormRequest $formRequest)
    {
        return new Template(
            $this->getTemplate(),
            [
                'form' => $formRequest->getForm()->createView(),
            ]
        );
    }

    public function getForm(FormFactoryInterface $factory, Options $options)
    {
        return $factory->create(ContactType::class, $options->get('contact'));
    }

    public function onSuccess(FormRequest $form, $contact): ?Response
    {
        /** @var Contact $contact */
        $this->save($contact);

        return $this->serialize($contact, ['client_api']);
    }

    abstract public function getTemplate(): string;

    // This needs to be public for the lazy proxy service definition to work

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('contact')
            ->setAllowedTypes('contact', Contact::class);
    }
}
