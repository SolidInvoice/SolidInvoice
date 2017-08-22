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

namespace SolidInvoice\InvoiceBundle\Tests\Form\Type;

use Cron\CronExpression;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Form\Type\RecurringInvoiceType;

class RecurringInvoiceTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $formData = [
            'frequency' => (string) CronExpression::factory('@weekly'),
            'date_start' => $this->faker->dateTime,
            'date_end' => $this->faker->dateTime,
        ];

        $recurringInvoice = new RecurringInvoice();
        $recurringInvoice->setFrequency('0 0 * * 0');

        $this->assertFormData(RecurringInvoiceType::class, $formData, $recurringInvoice);
    }
}
