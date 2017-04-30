<?php

namespace CSBill\ClientBundle\Action\Ajax;

use CSBill\ClientBundle\Entity\Address as Entity;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Address
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function __invoke(Request $request, Entity $address)
    {
        $context = SerializationContext::create()->setGroups(['js']);

        return new Response($this->serializer->serialize($address, 'json', $context), 200, ['Content-Type' => 'application/json']);
    }
}