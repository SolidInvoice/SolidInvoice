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

final readonly class CustomFieldReorderAction
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function __invoke(Request $request): Response
    {
        try {
            $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (! is_array($payload)) {
            return new JsonResponse(['error' => 'Expected array'], Response::HTTP_BAD_REQUEST);
        }

        $repo = $this->em->getRepository(CustomField::class);
        foreach ($payload as $row) {
            if (! is_array($row) || ! isset($row['id'], $row['position'])) {
                return new JsonResponse(['error' => 'Each row must contain "id" and "position".'], Response::HTTP_BAD_REQUEST);
            }

            try {
                $id = Ulid::fromString((string) $row['id']);
            } catch (Throwable) {
                return new JsonResponse(['error' => 'Invalid custom field id.'], Response::HTTP_BAD_REQUEST);
            }

            // CompanyFilter is global, so find() will return null for any ULID
            // outside the current company — we surface that as 404 instead of
            // silently skipping, to avoid leaking the existence of foreign IDs.
            $field = $repo->find($id);
            if ($field === null) {
                return new JsonResponse(['error' => 'Custom field not found.'], Response::HTTP_NOT_FOUND);
            }

            $field->setPosition((int) $row['position']);
        }

        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
