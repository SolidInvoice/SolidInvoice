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

namespace SolidInvoice\ProductBundle\Action;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\ProductBundle\Form\Type\ProductType;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class Edit extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly FeatureGate $featureGate,
    ) {
    }

    public function __invoke(Request $request, Product $product): Response
    {
        if (! $this->featureGate->isEnabled(Feature::Products->value)) {
            return $this->render('@SolidInvoiceProduct/Default/gated.html.twig');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();

            $this->addFlash('success', 'product.flash.saved');

            return $this->redirectToRoute('_products');
        }

        return $this->render('@SolidInvoiceProduct/Default/form.html.twig', ['form' => $form]);
    }
}
