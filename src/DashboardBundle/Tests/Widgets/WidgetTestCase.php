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

namespace SolidInvoice\DashboardBundle\Tests\Widgets;

use SolidInvoice\DashboardBundle\Widgets\WidgetInterface;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use Zenstruck\Foundry\Test\Factories;

abstract class WidgetTestCase extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;
    use MatchesSnapshots;

    protected Environment $twig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->twig = self::getContainer()->get('twig');
    }

    /**
     * Render a widget template with the given data and return the HTML.
     */
    protected function renderWidget(WidgetInterface $widget): string
    {
        return $this->normalizeHtml($this->twig->render($widget->getTemplate(), $widget->getData()));
    }

    /**
     * Normalize HTML for consistent snapshot comparison.
     * Replaces dynamic values like UUIDs, dates, and amounts.
     */
    protected function normalizeHtml(string $html): string
    {
        // Replace UUIDs
        $html = preg_replace(
            '#[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}#',
            '00000000-0000-0000-0000-000000000000',
            $html
        );

        // Replace ULIDs
        $html = preg_replace('#[0-9A-Za-z]{26}#', '01JBYEQCR7DJ2YW4EXP6FYJZCR', $html);

        // Replace month-year labels (e.g. "May 2025") with stable placeholders so
        // the chart snapshot does not need to be regenerated every month. Chart
        // payloads are rendered into HTML attributes where the JSON quotes are
        // entity-encoded (&quot;), so preserve that encoding in the replacement
        // to avoid breaking attribute boundaries during DOM serialization.
        $html = preg_replace(
            '#(?<q>"|&quot;)(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) \d{4}(?P=q)#',
            '$1MONTH YEAR$1',
            $html
        );

        return trim($html);
    }
}
