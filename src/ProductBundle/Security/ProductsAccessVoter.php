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

namespace SolidInvoice\ProductBundle\Security;

use SensitiveParameter;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Gates every Product API operation (REST and GraphQL) behind the `products` feature.
 *
 * It is wired through the `security` option of the Product API resource, so it is evaluated
 * once the request is authenticated and the company is known, which a kernel.request
 * listener cannot guarantee.
 *
 * @see \SolidInvoice\SaasBundle\Tests\Functional\ProductsApiGateTest
 * @extends Voter<string, mixed>
 */
final class ProductsAccessVoter extends Voter
{
    public const string ACCESS = 'PRODUCTS_API_ACCESS';

    public function __construct(
        private readonly FeatureGate $featureGate,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::ACCESS;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, #[SensitiveParameter] TokenInterface $token, ?Vote $vote = null): bool
    {
        if (! $this->featureGate->isEnabled(Feature::Products->value)) {
            $vote?->addReason('Products are not available on the current plan.');

            return false;
        }

        return true;
    }
}
