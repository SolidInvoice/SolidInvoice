<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Controller;

use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as Base;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseController extends Base
{
    /**
     * Get a doctrine repository.
     *
     * @param string $repository
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository($repository)
    {
        return $this->getEm()->getRepository($repository);
    }

    /**
     * Return a instance of the doctrine entity manager.
     *
     * @return \Doctrine\ORM\EntityManagerInterface
     */
    protected function getEm()
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
    protected function flash($message, $type = 'notice')
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
    protected function trans($message)
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
     * @param mixed $data
     * @param int   $responseCode
     *
     * @return Response
     */
    protected function serializeJs($data, $responseCode = 200)
    {
        $serializer = $this->get('serializer');

        $context = SerializationContext::create()->setGroups(['js']);

        $data = $serializer->serialize($data, 'json', $context);

        return new Response($data, $responseCode, ['Content-Type' => 'application/json']);
    }
}
