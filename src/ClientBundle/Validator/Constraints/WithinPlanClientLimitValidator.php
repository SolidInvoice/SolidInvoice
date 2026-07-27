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

namespace SolidInvoice\ClientBundle\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Mode\ModeResolver;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @see \SolidInvoice\ClientBundle\Tests\Validator\Constraints\WithinPlanClientLimitValidatorTest
 */
final class WithinPlanClientLimitValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly FeatureGate $featureGate,
        private readonly ModeResolver $modeResolver,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof WithinPlanClientLimit) {
            throw new UnexpectedTypeException($constraint, WithinPlanClientLimit::class);
        }

        if (! $value instanceof Client) {
            throw new UnexpectedValueException($value, Client::class);
        }

        // SaaS-only. On self-hosted the Noop feature gate allows everything anyway,
        // but the mode guard avoids the count query and makes the intent explicit.
        if (! $this->modeResolver->isSaas()) {
            return;
        }

        // Only new clients count against the limit; a managed (already-persisted) entity
        // is an edit and must not be blocked. The id cannot be used here because Client
        // assigns its ULID in the constructor, so it is never null.
        if ($this->entityManager->contains($value)) {
            return;
        }

        if (! $this->featureGate->canUse(Feature::TotalClients->value, $this->clientRepository->getTotalClients())) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
