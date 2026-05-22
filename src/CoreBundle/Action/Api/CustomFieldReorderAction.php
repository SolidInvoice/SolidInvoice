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

namespace SolidInvoice\CoreBundle\Action\Api;

use const JSON_THROW_ON_ERROR;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;
use Throwable;
use function is_array;
use function json_decode;

final class CustomFieldReorderAction
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    public function __invoke(Request $request): Response
    {
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
                return new JsonResponse(['error' => 'Each row must contain "id" and "position".'], 400);
            }

            try {
                $id = Ulid::fromString((string) $row['id']);
            } catch (Throwable) {
                return new JsonResponse(['error' => 'Invalid custom field id.'], 400);
            }

            // CompanyFilter is global, so find() will return null for any ULID
            // outside the current company — we surface that as 404 instead of
            // silently skipping, to avoid leaking the existence of foreign IDs.
            $field = $repo->find($id);
            if ($field === null) {
                return new JsonResponse(['error' => 'Custom field not found.'], 404);
            }

            $field->setPosition((int) $row['position']);
        }
        $this->em->flush();

        return new JsonResponse(null, 204);
    }
}
