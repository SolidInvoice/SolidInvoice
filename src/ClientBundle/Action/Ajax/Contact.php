<?php
/**
 * Created by PhpStorm.
 * User: pierre
 * Date: 2017/04/30
 * Time: 18:27
 */

namespace CSBill\ClientBundle\Action\Ajax;

use CSBill\ClientBundle\Entity\Contact as Entity;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Contact
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function __invoke(Request $request, Entity $contact)
    {
        $context = SerializationContext::create()->setGroups(['js']);

        return new Response($this->serializer->serialize($contact, 'json', $context), 200, ['Content-Type' => 'application/json']);
    }
}