<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Twig\Extension;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig_Extension;

class FileExtension extends Twig_Extension
{
    /**
     * @var RequestStack
     */
    private $request;

    /**
     * @param RequestStack $request
     */
    public function __construct(RequestStack $request)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('file', array($this, 'getFile'), array('is_safe' => array('css'))),
        );
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function getFile($url)
    {
        $request = $this->request->getCurrentRequest();

        $url = str_replace($request->getBaseUrl(), '', $url);

        return file_get_contents($request->getUriForPath($url));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'csbill_core.twig.file';
    }
}
