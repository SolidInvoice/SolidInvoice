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

namespace SolidInvoice\ProductBundle\Api;

use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * The single choke point that keeps every `/api/products` operation behind the `products`
 * feature. It runs after the router has resolved the request (priority below 32) and before
 * API Platform reads or deserializes anything (its listeners sit at 4 and below), so a
 * disabled feature never reaches the state providers or processors.
 *
 * @see \SolidInvoice\SaasBundle\Tests\Functional\ProductsApiGateTest
 */
final readonly class ProductsFeatureSubscriber
{
    public function __construct(
        private FeatureGate $featureGate,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 8)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->getRequest()->attributes->get('_api_resource_class') !== Product::class) {
            return;
        }

        if (! $this->featureGate->isEnabled(Feature::Products->value)) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Products are not available on the current plan.');
        }
    }
}
