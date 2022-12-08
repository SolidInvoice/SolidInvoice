<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Pdf;

use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use Psr\Log\LoggerInterface;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Pdf\GeneratorTest
 */
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
     * @throws MpdfException
     */
    public function generate(string $html): string
    {
        $mpdf = new Mpdf([
            'tempDir' => $this->cacheDir . '/pdf',
            'margin_left' => 20,
            'margin_right' => 15,
            'margin_top' => 48,
            'margin_bottom' => 25,
            'margin_header' => 10,
            'margin_footer' => 10,
        ]);

        $mpdf->showWatermarkText = true;
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->SetProtection(['print']);
        $mpdf->setLogger($this->logger);
        $mpdf->WriteHTML($html);

        return $mpdf->Output(null, Destination::STRING_RETURN);
    }

    public function canPrintPdf(): bool
    {
        return \extension_loaded('mbstring') && \extension_loaded('gd');
    }
}
