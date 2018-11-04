<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Pdf;

use Mpdf\Mpdf;
use Psr\Log\LoggerInterface;

class Generator
{
    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(string $cacheDir, LoggerInterface $logger)
    {
        $this->cacheDir = $cacheDir;
        $this->logger = $logger;
    }

    /**
     * @param string $html
     *
     * @throws \Mpdf\MpdfException
     */
    public function generate(string $html)
    {
        $mpdf = new Mpdf(['tempDir' => $this->cacheDir.'/pdf']);
        $mpdf->simpleTables = true;
        $mpdf->setLogger($this->logger);
        $mpdf->WriteHTML($html);
        $mpdf->Output();
    }
}