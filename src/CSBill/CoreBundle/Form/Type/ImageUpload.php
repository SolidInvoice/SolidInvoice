<?php

namespace CSBill\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use CSBill\CoreBundle\Security\Encryption;

class ImageUpload extends AbstractType
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
     * @param Session $session
     * @param Encryption $encryption
     */
    public function __construct(Session $session, Encryption $encryption)
    {
        $this->session = $session;
        $this->encryption = $encryption;
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $session_id = $this->session->getId();

        $view->vars['sessionId'] = $this->encryption->encrypt($session_id);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'text';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'image_upload';
    }
}
