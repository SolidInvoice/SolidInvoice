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

use CSBill\ClientBundle\Entity\Contact;
use CSBill\ClientBundle\Form\Type\ContactType;
use CSBill\CoreBundle\Templating\Template;
use CSBill\CoreBundle\Traits\SaveableTrait;
use CSBill\CoreBundle\Traits\SerializeTrait;
use SolidWorx\FormHandler\FormCollectionHandlerInterface;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerOptionsResolver;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\FormHandler\Options;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractContactFormHandler implements FormHandlerInterface, FormHandlerResponseInterface, FormCollectionHandlerInterface, FormHandlerSuccessInterface, FormHandlerOptionsResolver
{
    use SaveableTrait,
        SerializeTrait;

    /**
     * {@inheritdoc}
     */
    public function getResponse(FormRequest $formRequest)
    {
        if ($formRequest->getForm()->isSubmitted() && $formRequest->getForm()->isValid()) {
            return $this->serialize($formRequest->getForm()->getData());
        }

        return new Template(
            $this->getTemplate(),
            [
                'form' => $formRequest->getForm()->createView(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(FormFactoryInterface $factory = null, Options $options)
    {
        return $factory->create(ContactType::class, $options->get('contact'), ['allow_delete' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess($client, FormRequest $form): ?Response
    {
        /* @var Contact $client */
        $this->save($client);

        return $this->getResponse($form);
    }

    /**
     * @return string
     */
    abstract public function getTemplate(): string;

 // This needs to be public for the lazy proxy service definition to work

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('contact')
            ->setAllowedTypes('contact', Contact::class);
    }
}
