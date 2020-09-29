<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Model\Status;

/**
 * @codeCoverageIgnore
 */
class LoadData extends Fixture
{
    /**
     * {@inheritdoc}
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
