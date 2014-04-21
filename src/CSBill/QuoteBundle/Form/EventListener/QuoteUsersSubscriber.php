<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Form\EventListener;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CSBill\QuoteBundle\Entity\Quote;
use Symfony\Component\Validator\Constraints\NotBlank;

class QuoteUsersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SET_DATA => 'preSetData', FormEvents::PRE_SUBMIT => 'preSetData');
    }

    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data) {
            return;
        }

        if ($data instanceof Quote) {
            $client_id = !is_null($data->getClient()) ? $data->getClient()->getId() : null;
        } else {
            $client_id = isset($data['client']) ? $data['client'] : null;
        }

        if (!empty($client_id)) {
            $form->add(
                'users',
                'entity',
                array(
                    'constraints' => array(
                        new NotBlank()
                    ),
                    'multiple' => true,
                    'expanded' => true,
                    'class' => 'CSBillClientBundle:Contact',
                    'query_builder' => function (EntityRepository $repo) use ($client_id) {
                        return $repo->createQueryBuilder('c')->where('c.client = :client')->setParameter('client', $client_id);
                    }
                )
            );
        }
    }
}
