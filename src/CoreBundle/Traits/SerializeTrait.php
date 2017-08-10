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
     *
     * @throws \Exception
     */
    protected function serialize($object, array $groups = ['js'], Response $response = null): Response
    {
        if (!$this->serializer) {
            throw new \Exception(sprintf('You need to call %s::setSerializer with a valid %s instance before calling %s', get_class($this), SerializerInterface::class, __METHOD__));
        }

        if (!$response) {
            $response = new JsonResponse('', 200, [], true);
        }

        $json = $this->serializer->serialize($object, 'json', []);

        if ($response instanceof JsonResponse) {
            $response->setJson($json);
        } else {
            $response->setContent($json);
        }

        return $response;
    }
}
