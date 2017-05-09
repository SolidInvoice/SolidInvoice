<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Form\Handler;

use CSBill\CoreBundle\Templating\Template;
use SolidWorx\FormHandler\FormRequest;

class QuoteCreateHandler extends AbstractQuoteHandler
{
    /**
     * {@inheritdoc}
     */
    public function getResponse(FormRequest $formRequest)
    {
        return new Template(
            '@CSBillQuote/Default/create.html.twig',
            [
                'form' => $formRequest->getForm()->createView(),
            ]
        );
    }
}
