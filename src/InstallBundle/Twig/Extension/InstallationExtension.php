<?php
declare(strict_types=1);

namespace SolidInvoice\InstallBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class InstallationExtension extends AbstractExtension
{
    private ?string $installed;

    public function __construct(?string $installed)
    {
        $this->installed = $installed;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_is_installed', fn () => null !== $this->installed)
        ];
    }

}
