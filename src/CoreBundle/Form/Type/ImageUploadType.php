<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Form\Type;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
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
     * @var string
     */
    private $secret;

    /**
     * @param SessionInterface $session
     * @param string           $secret
     */
    public function __construct(SessionInterface $session, string $secret)
    {
        $this->session = $session;
        $this->secret = $secret;
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $sessionId = $this->session->getId();

        $view->vars['sessionId'] = Crypto::encrypt($sessionId, Key::loadFromAsciiSafeString($this->secret));
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
