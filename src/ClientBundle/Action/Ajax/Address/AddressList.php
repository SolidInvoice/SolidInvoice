<?php

namespace CSBill\ClientBundle\Action\Ajax\Address;

use CSBill\ClientBundle\Entity\Client;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AddressList
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function __invoke(Request $request, Client $client)
    {
        $context = SerializationContext::create()->setGroups(['js']);

        return new Response($this->serializer->serialize([], 'json', $context), 200, ['Content-Type' => 'application/json']);
    }
}