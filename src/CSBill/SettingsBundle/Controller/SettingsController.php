<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\SettingsBundle\Form\Type\SettingsType;
use CSBill\SettingsBundle\Model\Setting;

/**
 * Class SettingsController
 * @package CSBill\SettingsBundle\Controller
 */
class SettingsController extends BaseController
{
    /**
     * Settings action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        /** @var \CSBill\SettingsBundle\Manager\SettingsManager $manager */
        $manager = $this->get('settings');

        $settings = $manager->getSettings()->toArray();

        array_walk_recursive($settings, function (Setting &$setting) {
            $setting = $setting->getValue();
        });

        $form = $this->createForm(new SettingsType(), $settings, array('manager' => $manager));

        $request = $this->getRequest();

        if ($request->isMethod('POST')) {
            $form->bind($request);

            $manager->set($request->request->get('settings'));

            $this->flash($this->trans('Settings saved successfully!'), 'success');

            return $this->redirect($this->generateUrl($request->get('_route')));
        }

        return $this->render('CSBillSettingsBundle:Settings:index.html.twig', array('settings' => $settings, 'form' => $form->createView()));
    }
}
