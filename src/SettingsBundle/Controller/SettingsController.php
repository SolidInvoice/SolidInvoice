<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\SettingsBundle\Form\Type\SettingSectionType;
use CSBill\SettingsBundle\Model\Setting;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SettingsController.
 */
class SettingsController extends BaseController
{
    /**
     * Settings action.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request): Response
    {
        /** @var \CSBill\SettingsBundle\Manager\SettingsManager $manager */
        $manager = $this->get('settings');

        $settings = $manager->getSettings();

        array_walk_recursive($settings, function (Setting &$setting): Response {
            $setting = $setting->getValue();
        });

        $form = $this->createForm(SettingSectionType::class, $settings, ['manager' => $manager]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $manager->set($request->request->get('settings'));
            } catch (\Exception $e) {
                $this->flash($this->trans($e->getMessage()), 'error');

                return $this->redirectToRoute($request->get('_route'));
            }

            $this->flash($this->trans('settings.saved.success'), 'success');

            return $this->redirectToRoute($request->get('_route'));
        }

        return $this->render(
            'CSBillSettingsBundle:Settings:index.html.twig',
            [
                'appSettings' => $settings,
                'form' => $form->createView(),
            ]
        );
    }
}
