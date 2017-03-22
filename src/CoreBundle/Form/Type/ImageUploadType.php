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

namespace CSBill\CoreBundle\Form\Type;

use CSBill\CoreBundle\Security\Encryption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ImageUploadType extends AbstractType
{
    /**
     * @var \Symfony\Component\HttpFoundation\Session\Session
     */
    protected $session;

    /**
     * @var \CSBill\CoreBundle\Security\Encryption
     */
    protected $encryption;

    /**
     * @param SessionInterface $session
     * @param Encryption       $encryption
     */
    public function __construct(SessionInterface $session, Encryption $encryption)
    {
        $this->session = $session;
        $this->encryption = $encryption;
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $sessionId = $this->session->getId();

        $view->vars['sessionId'] = $this->encryption->encrypt($sessionId);
    }

    /**
     * @return string
     */
    public function getParent(): string
    {
        return TextType::class;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'image_upload';
    }
}
