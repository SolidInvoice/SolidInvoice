<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\Tests\Twig\Components;

use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\PaymentBundle\Twig\Components\PaymentMethods;

final class PaymentMethodsTest extends LiveComponentTest
{
    public function testRenderPaymentMethods(): void
    {
        $component = $this->createLiveComponent(
            name: PaymentMethods::class,
            client: $this->client,
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($component->render()->toString());
    }

    public function testSwitchPaymentMethod(): void
    {
        $component = $this->createLiveComponent(
            name: PaymentMethods::class,
            client: $this->client,
        )->actingAs($this->getUser());

        $component->set('method', 'cash');

        $this->assertMatchesHtmlSnapshot($component->render()->toString());
    }
}
