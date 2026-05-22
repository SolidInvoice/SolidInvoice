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

namespace SolidInvoice\SettingsBundle\Action\CustomField;

use const JSON_THROW_ON_ERROR;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Uid\Ulid;
use function is_array;
use function json_decode;

final class ReorderAction
{
    public const string CSRF_TOKEN_ID = 'custom_field_reorder';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FeatureGate $featureGate,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (! $this->featureGate->isEnabled(Feature::CustomFields->value)) {
            return new JsonResponse(['error' => 'Custom fields are not available on the current plan.'], 403);
        }

        $token = (string) $request->headers->get('X-CSRF-Token', '');
        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $token))) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], 403);
        }

        try {
            $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        if (! is_array($payload)) {
            return new JsonResponse(['error' => 'Expected array'], 400);
        }

        $repo = $this->em->getRepository(CustomField::class);
        foreach ($payload as $row) {
            if (! is_array($row) || ! isset($row['id'], $row['position'])) {
                continue;
            }
            $field = $repo->find(Ulid::fromString((string) $row['id']));
            if ($field !== null) {
                $field->setPosition((int) $row['position']);
            }
        }
        $this->em->flush();

        return new JsonResponse(null, 204);
    }
}
