<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\UserBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\UserBundle\Entity\ApiToken;
use CSBill\UserBundle\Entity\User;
use RandomLib\Factory;
use RandomLib\Generator;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends BaseController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render(
            'CSBillUserBundle:Api:index.html.twig',
            array(
                'tokens' => $user->getApiTokens(),
            )
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function saveTokenAction(Request $request)
    {
        $apiToken = new ApiToken();
        $apiToken->setUser($this->getUser());

        $formBuilder = $this->createFormBuilder(
            $apiToken,
            array(
                'action' => $this->generateUrl('api_key_create'),
                'data_class' => 'CSBill\UserBundle\Entity\ApiToken'
            )
        );

        $formBuilder->add('name');

        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        $response = array();

        if ($form->isValid()) {
            $factory = new Factory;
            $generator = $factory->getMediumStrengthGenerator();

            $token = $generator->generateString(64, Generator::CHAR_ALNUM);
            $apiToken->setToken($token);

            $this->save($apiToken);

            $response['status'] = 0;
            $response['token'] = array(
                'token' => $apiToken->getToken(),
                'name' => $apiToken->getName(),
                'id' => $apiToken->getId()
            );

            return $this->json($response);
        } else {
            $response['status'] = 1;
        }

        $content = $this->renderView(
            'CSBillUserBundle:Api:create.html.twig',
            array(
                'form' => $form->createView(),
            )
        );

        $response['content'] = $content;

        return $this->json($response);
    }

    /**
     * @param ApiToken $token
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function revokeTokenAction(ApiToken $token)
    {
        $this->getEm()->remove($token);
        $this->getEm()->flush();

        return $this->json(
            array(
                'status' => 0
            )
        );
    }
}