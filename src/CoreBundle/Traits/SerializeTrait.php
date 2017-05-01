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

namespace CSBill\CoreBundle\Traits;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait SerializeTrait
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param SerializerInterface $serializer
     *
     * @required
     */
    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    /**
     * @param mixed         $object
     * @param array         $groups
     * @param Response|null $response
     *
     * @return Response
     */
    protected function serialize($object, array $groups = ['js'], Response $response = null): Response
    {
        $context = SerializationContext::create()->setGroups($groups);

        if (!$response) {
            $response = new JsonResponse('', 200, [], true);
        }

        $json = $this->serializer->serialize($object, 'json', $context);

        if ($response instanceof JsonResponse) {
            $response->setJson($json);
        } else {
            $response->setContent($json);
        }

        return $response;
    }
}
