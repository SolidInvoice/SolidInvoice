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

namespace SolidInvoice\InstallBundle\Action;

use const JSON_THROW_ON_ERROR;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InstallBundle\Installer\Database\Migration;
use SolidInvoice\InstallBundle\Step\InstallationStepInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use function flush;
use function iterator_to_array;
use function json_encode;
use function Symfony\Component\String\u;

final class Install extends AbstractController
{
    /**
     * @param ServiceLocator<InstallationStepInterface> $steps
     */
    public function __construct(
        #[AutowireLocator(services: InstallationStepInterface::DI_TAG, defaultIndexMethod: 'getLabel', defaultPriorityMethod: 'priority')]
        private readonly ServiceLocator $steps
    ) {
    }

    public function __invoke(Request $request, ManagerRegistry $doctrine, Migration $migration): Response
    {
        if ($request->query->has('action')) {
            $action = u($request->query->get('action'))->replace('_', ' ')->title()->toString();

            if (! $this->steps->has($action)) {
                throw new BadRequestException('Invalid action');
            }

            $step = $this->steps->get($action);

            return new StreamedResponse(function () use ($step): void {

                try {
                    $step->execute(function (string $content): void {
                        echo 'data: ' . json_encode(['output' => $content], JSON_THROW_ON_ERROR) . "\n\n";
                        flush();
                    });

                    echo 'data: ' . json_encode(['status' => 'done'], JSON_THROW_ON_ERROR) . "\n\n";
                    flush();
                } catch (Throwable $e) {
                    echo 'error: ' . json_encode(['message' => $e->getMessage()], JSON_THROW_ON_ERROR) . "\n\n";
                    flush();
                }
            }, headers: ['content-type' => 'text/event-stream']);
        }

        return $this->render('@SolidInvoiceInstall/install.html.twig', ['steps' => iterator_to_array($this->steps->getIterator())]);
    }
}
