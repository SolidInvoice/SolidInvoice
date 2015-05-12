<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Mailer;

use CSBill\CoreBundle\Mailer\Exception\UnexpectedFormatException;
use CSBill\PaymentBundle\Entity\Payment;
use CSBill\SettingsBundle\Manager\SettingsManager;
use CSBill\UserBundle\Entity\User;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PaymentMailer
{
    const PAYMENT_TEMPLATE_HTML = 'CSBillPaymentBundle:Email:payment.html.twig';

    const PAYMENT_TEMPLATE_TEXT = 'CSBillPaymentBundle:Email:payment.txt.twig';

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var SettingsManager
     */
    private $settings;

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param EngineInterface     $templating
     * @param SettingsManager     $settings
     * @param ManagerRegistry     $doctrine
     * @param TranslatorInterface $translator
     */
    public function __construct(
        EngineInterface $templating,
        SettingsManager $settings,
        ManagerRegistry $doctrine,
        TranslatorInterface $translator
    ) {
        $this->doctrine = $doctrine;
        $this->settings = $settings;
        $this->templating = $templating;
        $this->translator = $translator;
    }

    /**
     * @param Payment $payment
     *
     * @return \Swift_Message
     * @throws UnexpectedFormatException
     */
    public function createPaymentMail(Payment $payment)
    {
        $userRepository = $this->doctrine->getManager()->getRepository('CSBillUserBundle:User');

        $users = array_map(function (User $user) {
            return $user->getEmail();
        }, $userRepository->findAll());

        $message = new \Swift_Message();
        $message->setTo($users);

        $fromAddress = (string) $this->settings->get('email.from_address');
        $fromName = (string) $this->settings->get('email.from_name');
        $message->setFrom($fromAddress, $fromName);

        $message->setSubject($this->translator->trans('payment.email.capture.subject'));

        $format = (string) $this->settings->get('email.format');

        $htmlTemplate = $this->templating->render(
            self::PAYMENT_TEMPLATE_HTML,
            array(
                'payment' => $payment
            )
        );

        $textTemplate = $this->templating->render(
            self::PAYMENT_TEMPLATE_TEXT,
            array(
                'payment' => $payment
            )
        );

        switch ($format) {
            case 'html':
                $message->setBody($htmlTemplate, 'text/html');
                break;

            case 'text':
                $message->setBody($textTemplate, 'text/plain');
                break;

            case 'both':
                $message->setBody($htmlTemplate, 'text/html');
                $message->addPart($textTemplate, 'text/plain');
                break;

            default:
                throw new UnexpectedFormatException($format);
        }

        return $message;
    }
}