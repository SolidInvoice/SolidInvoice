<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InstallBundle\Installer\Step;

use Symfony\Component\Process\Process;

use CSBill\InstallBundle\Installer\Step;
use CSBill\UserBundle\Entity\User;

class SystemInformation extends Step
{
    /**
     * The view to render for this installation step
     *
     * @var string $view;
     */
    public $view = 'CSBillInstallBundle:Install:system_information.html.twig';

    /**
     * The title to display when this installation step is active
     *
     * @var string $title
     */
    public $title = 'System Information';

    /**
     * Array containing all the parameters for the system and user information
     *
     * @var array $params
     */
    public $params = array(	'email_address' => '',
                            'password'		=> '',
                            'username'		=> ''
                            );

    /**
     * Validate user and company info
     *
     * @param  array   $request
     * @return boolean
     */
    public function validate(array $request)
    {
        if (empty($request['email_address'])) {
            $this->addError('Please enter an email address');
        }

        if (empty($request['password'])) {
            $this->addError('Please enter a password');
        }

        if (empty($request['username'])) {
            $this->addError('Please enter a username');
        }

        $this->params = $request;

        return count($this->getErrors()) === 0;
    }

    /**
     * Save system and user configuration values
     *
     * @param array $request
     */
    public function process(array $request)
    {
        $user = new User;

        $encoder = $this->get('security.encoder_factory')->getEncoder($user);

        $password = $encoder->encodePassword($request['password'], $user->getSalt());

        $user->setUsername($request['username'])
             ->setEmail($request['email_address'])
             ->setPassword($password);

        $em = $this->get('doctrine.orm.entity_manager');

        $role = $em->getRepository('CSBillUserBundle:Role')->findOneByName('super_admin');

        $user->addRole($role);

        $em->persist($user);
        $em->flush();
    }

    /**
     * @return void
     */
    public function start()
    {
    }
}
