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

namespace SolidInvoice\CoreBundle\Traits;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

trait SerializeTrait
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @required
     */
    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    /**
     * @param mixed $object
     *
     * @throws Exception
     */
    protected function serialize($object, array $groups = [], Response $response = null): Response
    {
        if (! $this->serializer) {
            throw new Exception(sprintf('You need to call %s::setSerializer with a valid %s instance before calling %s', static::class, SerializerInterface::class, __METHOD__));
        }

        if (! $response instanceof Response) {
            $response = new JsonResponse('', Response::HTTP_OK, [], true);
        }

        $json = $this->serializer->serialize($object, 'json', ['groups' => $groups]);

        if ($response instanceof JsonResponse) {
            $response->setJson($json);
        } else {
            $response->setContent($json);
        }

        return $response;
    }
}
