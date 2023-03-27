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

use Doctrine\ORM\EntityRepository;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Form\Transformer\UserToContactTransformer;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\InvoiceContact;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
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

    public function __construct(FormBuilderInterface $builder, BaseInvoice $invoice)
    {
        $this->builder = $builder;
        $this->invoice = $invoice;
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
            $clientId = is_null($data->getClient()) ? null : $data->getClient()->getId();
        } else {
            $clientId = $data['client'] ?? null;
        }

        if (! empty($clientId)) {
            $form = $event->getForm();

            $invoice = $this->invoice;

            $users = $this->builder->create(
                'users',
                EntityType::class,
                [
                    'constraints' => new NotBlank(),
                    'multiple' => true,
                    'expanded' => true,
                    'auto_initialize' => false,
                    'class' => Contact::class,
                    'query_builder' => function (EntityRepository $repo) use ($clientId) {
                        return $repo->createQueryBuilder('c')
                            ->where('c.client = :client')
                            ->setParameter('client', $clientId);
                    },
                ]
            )->addModelTransformer(new UserToContactTransformer($invoice, InvoiceContact::class));

            $form->add($users->getForm());
        }
    }
}
