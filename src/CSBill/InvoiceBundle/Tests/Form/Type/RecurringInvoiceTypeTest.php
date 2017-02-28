<?php
/**
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Tests\Form\Type;

use Cron\CronExpression;
use CSBill\CoreBundle\Tests\FormTestCase;
use CSBill\InvoiceBundle\Entity\RecurringInvoice;
use CSBill\InvoiceBundle\Form\Type\RecurringInvoiceType;

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
