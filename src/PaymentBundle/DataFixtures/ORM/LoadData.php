<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2019
 */

namespace SolidInvoice\PaymentBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Model\Status;

class LoadData extends Fixture
{
    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $payment = new Payment();
        $payment->setClient($this->getReference('client'));
        $payment->setDescription('Payment');
        $payment->setStatus(Status::STATUS_CAPTURED);

        $this->setReference('payment', $payment);

        $manager->persist($payment);
        $manager->flush();
    }
}
