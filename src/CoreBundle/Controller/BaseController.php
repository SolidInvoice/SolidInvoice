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

namespace CSBill\CoreBundle\Controller;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as Base;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseController extends Base
{
    /**
     * Get a doctrine repository.
     *
     * @param string $repository
     *
     * @return ObjectRepository
     */
    protected function getRepository(string $repository): ObjectRepository
    {
        return $this->getEm()->getRepository($repository);
    }

    /**
     * Return a instance of the doctrine entity manager.
     *
     * @return ObjectManager
     */
    protected function getEm(): ObjectManager
    {
        return $this->getDoctrine()->getManager();
    }

    /**
     * Adds a message to the session flash.
     *
     * @param string $message The message to add to the session flash
     * @param string $type    The flash message type (notice, success, error etc)
     *
     * @return $this
     */
    protected function flash(string $message, string $type = 'notice')
    {
        $this->get('session')->getFlashBag()->add($type, $message);

        return $this;
    }

    /**
     * Translates a message.
     *
     * @param string $message
     *
     * @return string
     */
    protected function trans(string $message): string
    {
        return $this->get('translator')->trans($message);
    }

    /**
     * @param mixed $entity
     *
     * @return $this
     */
    protected function save($entity)
    {
        $entityManager = $this->getEm();
        $entityManager->persist($entity);
        $entityManager->flush();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function json($data, $status = 200, $headers = [], $_ = []): JsonResponse
    {
        $json = $this->container->get('serializer')->serialize($data, 'json');

        return new JsonResponse($json, $status, $headers, true);
    }

    /**
     * @param mixed $data
     * @param int   $responseCode
     *
     * @return Response
     */
    protected function serializeJs($data, int $responseCode = 200): Response
    {
        $serializer = $this->get('serializer');

        $context = SerializationContext::create()->setGroups(['js']);

        $data = $serializer->serialize($data, 'json', $context);

        return new Response($data, $responseCode, ['Content-Type' => 'application/json']);
    }
}
