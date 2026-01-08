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

namespace SolidInvoice\TaxBundle\Action;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Form\Type\TaxType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use function assert;

final class Edit
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly RouterInterface $router,
        private readonly ManagerRegistry $doctrine
    ) {
    }

    public function __invoke(Request $request, Tax $tax): Template | Response
    {
        $form = $this->formFactory->create(TaxType::class, $tax);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();

            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('success', 'Tax rate successfully saved');

            return new RedirectResponse($this->router->generate('_tax_rates'));
        }

        return new Template('@SolidInvoiceTax/Default/form.html.twig', ['form' => $form->createView()]);
    }
}
