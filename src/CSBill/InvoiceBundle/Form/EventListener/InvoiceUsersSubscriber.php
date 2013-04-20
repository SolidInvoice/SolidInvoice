<?php

namespace CSBill\InvoiceBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CSBill\InvoiceBundle\Entity\Invoice;
use Symfony\Component\Validator\Constraints\NotBlank;

class InvoiceUsersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SET_DATA => 'preSetData', FormEvents::PRE_BIND => 'preSetData');
    }

    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data) {
            return;
        }

        if ($data instanceof Invoice) {
            $client_id = !is_null($data->getClient()) ? $data->getClient()->getId() : null;
        } else {
            $client_id = isset($data['client']) ? $data['client'] : null;
        }

        if (!empty($client_id)) {
            $factory = $form->getConfig()->getFormFactory();
            $form->add('users', 'entity', array(
                                                'constraints' => array(
                                                                        new NotBlank()
                                                                        ),
                                                'multiple' => true,
                                                'expanded' => true,
                                                'class' => 'CSBillClientBundle:Contact',
                                                'query_builder' => function($repo) use ($client_id) {
                                                                        $qb = $repo->createQueryBuilder('c')
                                                                                   ->where('c.client = :client')
                                                                                   ->setParameter('client', $client_id);

                                                                        return $qb;
                                                                    }
                                                ));
        }
    }
}
