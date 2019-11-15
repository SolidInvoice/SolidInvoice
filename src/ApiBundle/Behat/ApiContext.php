<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ApiBundle\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Behatch\Context\JsonContext;
use Behatch\Context\RestContext;
use Behatch\Json\Json;
use SolidInvoice\CoreBundle\Behat\DefaultContext;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Knp\FriendlyContexts\Context\EntityContext;
use PHPUnit\Framework\Assert;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @codeCoverageIgnore
 */
class ApiContext extends DefaultContext implements Context
{
    /**
     * @var RestContext
     */
    private $restContext;

    /**
     * @var EntityContext
     */
    private $entityContext;

    /**
     * @var JsonContext
     */
    private $jsonContext;

    /**
     * @BeforeScenario @api
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        /** @var InitializedContextEnvironment $environment */
        $environment = $scope->getEnvironment();

        $this->entityContext = $environment->getContext(EntityContext::class);
        $this->jsonContext = $environment->getContext(JsonContext::class);
        $this->restContext = $environment->getContext(RestContext::class);
    }

    /**
     * @Given I send a JSON :method request to :url
     */
    public function iSendAJSONRequestTo($method, $url)
    {
        $this->prepareRequest($url);

        return $this->restContext->iSendARequestTo($method, $url);
    }

    /**
     * Sends a HTTP request with a body.
     *
     * @Given I send a JSON :method request to :url with body:
     */
    public function iSendAJSONRequestToWithBody($method, $url, PyStringNode $body)
    {
        $this->prepareRequest($url, $body);

        return $this->restContext->iSendARequestTo($method, $url, $body);
    }

    /**
     * @Given I am authorised as :user
     */
    public function iAmAuthorisedAs($username)
    {
        $doctrine = $this->getContainer()->get('doctrine');

        /* @var User $user */
        $user = $doctrine->getRepository(User::class)->findOneBy(['username' => $username]);

        if (!$user) {
            throw new \Exception("Invalid User: \"$username\"");
        }

        $this->restContext->iAddHeaderEqualTo('X-API-TOKEN', $this->getUserApiToken($user));
    }

    private function getLastRecord(string $entity)
    {
        $getRecordBag = new \ReflectionMethod($this->entityContext, 'getRecordBag');
        $getRecordBag->setAccessible(true);
        /** @var \Knp\FriendlyContexts\Record\Collection\Bag $recordBag */
        $recordBag = $getRecordBag->invoke($this->entityContext);

        $collection = $recordBag->getCollection($this->resolveEntity($entity))->all();

        if (0 === count($collection)) {
            throw new \Exception("No records found for $entity");
        }

        return array_pop($collection)->getEntity();
    }

    private function prepareRequest(string &$url, PyStringNode &$body = null): void
    {
        $this->restContext->iAddHeaderEqualTo('Content-Type', 'application/ld+json');
        $this->restContext->iAddHeaderEqualTo('Accept', 'application/ld+json');

        $url = $this->replaceEntity($url);

        if (!empty($body)) {
            $body = new PyStringNode(array_map([$this, 'replaceEntity'], $body->getStrings()), $body->getLine());
        }
    }

    private function &replaceEntity(string &$string)
    {
        if (preg_match_all('/{([a-zA-Z\.]+)}/', $string, $matches)) {
            $collections = [];
            $accessor = PropertyAccess::createPropertyAccessor();

            foreach ($matches[1] as $match) {
                $entityName = substr($match, 0, strpos($match, '.'));

                try {
                    $entity = $collections[$entityName] ?? $collections[$entityName] = $this->getLastRecord($entityName);
                } catch (\Exception $e) {
                    continue;
                }

                $string = str_replace("{{$match}}", $accessor->getValue((object) [$entityName => $entity], $match), $string);
            }
        }

        return $string;
    }

    private function resolveEntity(string $entity): string
    {
        $method = new \ReflectionMethod($this->entityContext, 'resolveEntity');
        $method->setAccessible(true);

        return $method->invoke($this->entityContext, $entity)->getName();
    }

    private function getUserApiToken(User $user): string
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $tokenManager = $this->getContainer()->get('api.token.manager');

        if ($user->getApiTokens()->count() > 0) {
            return $user->getApiTokens()[0]->getToken();
        }

        $token = $tokenManager->generateToken();
        $user->setApiTokens(new ArrayCollection([(new ApiToken())->setName('behat')->setToken($token)->setUser($user)]));
        $em = $doctrine->getManager();
        $em->persist($user);
        $em->flush();

        return $token;
    }

    /**
     * @Given /^the JSON response should contain:$/
     */
    public function theJSONResponseShouldContain(PyStringNode $string)
    {
        $reflection = new \ReflectionMethod(JsonContext::class, 'getJson');
        $reflection->setAccessible(true);
        /* @var Json $response */
        $response = $reflection->invoke($this->jsonContext);

        $result = (array) (new Json($string->getRaw()))->getContent();

        $validator = new class()
        {
            private $propertyAccess;

            public function __construct()
            {
                $this->propertyAccess = PropertyAccess::createPropertyAccessor();
            }

            public function validate(array $expected, Json $actual)
            {
                foreach ($expected as $key => $value) {
                    if (is_array($value)) {
                        $child = (array) $actual->read($key, $this->propertyAccess);

                        sort($child);

                        foreach ($child as $k => $v) {
                            $this->validate((array) $value[$k], new Json(json_encode($v)));
                        }

                        continue;
                    }

                    if (is_string($actual->getContent())) {
                        Assert::assertEquals($value, $actual->getContent());
                    } else {
                        Assert::assertEquals($value, $actual->read($key, $this->propertyAccess));
                    }
                }
            }
        };

        $validator->validate($result, $response);
    }
}
