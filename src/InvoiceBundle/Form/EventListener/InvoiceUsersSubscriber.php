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

namespace SolidInvoice\InvoiceBundle\Form\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Form\Transformer\UserToContactTransformer;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\InvoiceContact;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceContact;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

final class InvoiceUsersSubscriber implements EventSubscriberInterface
{
    private FormBuilderInterface $builder;

    private BaseInvoice $invoice;

    private ManagerRegistry $registry;

    public function __construct(FormBuilderInterface $builder, BaseInvoice $invoice, ManagerRegistry $registry)
    {
        $this->builder = $builder;
        $this->invoice = $invoice;
        $this->registry = $registry;
    }

    /**
     * @return array<string, string>
     */
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

        if ($data instanceof Invoice || $data instanceof RecurringInvoice) {
            $clientId = $data->getClient() !== null && $data->getClient()->getId() !== null ? $data->getClient()->getId()->toString() : null;
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
            )->addModelTransformer(
                new UserToContactTransformer(
                    $this->invoice,
                    $this->invoice instanceof RecurringInvoice ? RecurringInvoiceContact::class : InvoiceContact::class
                )
            );

            $form->add($users->getForm());
        }
    }
}
