<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\UserBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\UserBundle\Entity\ApiToken;
use CSBill\UserBundle\Repository\ApiTokenRepository;
use RandomLib\Factory;
use RandomLib\Generator;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends BaseController
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            /** @var ApiTokenRepository $repository */
            $repository = $this->getRepository('CSBillUserBundle:ApiToken');

            $tokens = $repository->getApiTokensForUser($this->getUser());

            return $this->serializeJs($tokens);
        }

        return $this->render('CSBillUserBundle:Api:index.html.twig');
    }

    /**
     * @param ApiToken $token
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function tokenHistoryAction(ApiToken $token)
    {
        $content = $this->renderView(
            'CSBillUserBundle:Api:history.html.twig',
            array(
                'history' => $token->getHistory(),
            )
        );

        return $this->json(
            array(
                'content' => $content,
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
                'data_class' => 'CSBill\UserBundle\Entity\ApiToken',
            )
        );

        $formBuilder->add('name');

        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        $response = array();

        if ($form->isValid()) {
            $factory = new Factory();
            $generator = $factory->getMediumStrengthGenerator();

            $token = $generator->generateString(64, Generator::CHAR_ALNUM);
            $apiToken->setToken($token);

            $this->save($apiToken);

            $response['status'] = 'success';
            $response['token'] = array(
                'token' => $apiToken->getToken(),
                'name' => $apiToken->getName(),
                'id' => $apiToken->getId(),
            );

            return $this->json($response);
        } else {
            $response['status'] = 'failure';
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

        return $this->json([]);
    }
}
