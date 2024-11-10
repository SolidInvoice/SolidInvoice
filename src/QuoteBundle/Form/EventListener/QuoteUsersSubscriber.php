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

namespace SolidInvoice\QuoteBundle\Form\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Form\Transformer\UserToContactTransformer;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Entity\QuoteContact;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

class QuoteUsersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly FormBuilderInterface $builder,
        private readonly Quote $quote,
        private readonly ManagerRegistry $registry
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSetData',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        $data = $event->getData();

        if (null === $data) {
            return;
        }

        if ($data instanceof Quote) {
            $clientId = $data->getClient()?->getId()?->toString();
        } else {
            $clientId = $data['client'] ?? null;
        }

        if (! empty($clientId)) {
            $form = $event->getForm();

            $users = $this->builder->create(
                'users',
                EntityType::class,
                [
                    'constraints' => new NotBlank(),
                    'multiple' => true,
                    'expanded' => true,
                    'auto_initialize' => false,
                    'class' => Contact::class,
                    'choices' => $this->registry->getRepository(Contact::class)->findBy(['client' => $clientId]),
                ]
            )->addModelTransformer(new UserToContactTransformer($this->quote, QuoteContact::class));

            $form->add($users->getForm());
        }
    }
}
