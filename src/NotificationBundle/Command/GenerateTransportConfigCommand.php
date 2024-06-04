<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\NotificationBundle\Command;

use Composer\InstalledVersions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function count;
use function parse_url;
use function sprintf;
use function str_replace;
use function ucfirst;

/**
 * @codeCoverageIgnore
 */
#[AsCommand(
    name: 'solidinvoice:dev:notification:generate-transport-config',
    description: 'Generates a transport configuration for a notifier transport'
)]
final class GenerateTransportConfigCommand extends Command
{
    public function __construct(
        private readonly Environment $twig,
        private readonly string $env,
    ) {
        parent::__construct();
    }

    public function isEnabled(): bool
    {
        return $this->env === 'dev';
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = require dirname(__DIR__) . '/Resources/config/transports.php';

        $io = new SymfonyStyle($input, $output);

        foreach (['texter', 'chatter'] as $type) {
            $configs = $config[$type];

            $this->generate($io, $type, $configs);
        }

        return 0;
    }

    /**
     * @param array<string, array<string, string>> $configs
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    private function generate(SymfonyStyle $io, string $type, array $configs): void
    {
        $requiredPackages = [];

        $fs = new Filesystem();
        $io->title(sprintf('Generating %s transport configurations', $type));

        $progress = $io->createProgressBar(count($configs));
        $progress->start();

        foreach ($configs as $name => $config) {
            if (! InstalledVersions::isInstalled($config['package'])) {
                $requiredPackages[] = $config['package'];
            }

            $name = ucfirst($name);
            [$dsn, $options] = $this->parseDsn($config['dsn']);

            $form = $this->twig->render('@SolidInvoiceNotification/dev/TransportForm.text.twig', ['name' => $name, 'fields' => $options]);

            $fs->dumpFile(sprintf('%s/Form/Type/Transport/%sType.php', dirname(__DIR__), $name), $form);

            $configurator = $this->twig->render(
                '@SolidInvoiceNotification/dev/TransportConfiguration.text.twig',
                [
                    'name' => $name,
                    'dsn' => $dsn,
                    'options' => $options,
                    'type' => $type,
                ]
            );

            $fs->dumpFile(sprintf('%s/Configurator/%sConfigurator.php', dirname(__DIR__), $name), $configurator);

            $progress->advance();
        }

        $progress->finish();
        $progress->clear();

        $io->success('Done');

        if (count($requiredPackages) > 0) {
            $io->warning('The following packages are missing:');
            $io->listing($requiredPackages);

            $io->text('Please run `composer require ' . implode(' ', $requiredPackages) . '` to install the required packages');
        }
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function parseDsn(string $dsn): array
    {
        $parsed = parse_url($dsn);

        $options = [];

        if (isset($parsed['user'])) {
            $options[$parsed['user']] = strtolower($parsed['user']);

            $dsn = str_replace($parsed['user'], '%s', $dsn);
        }

        if (isset($parsed['pass'])) {
            $options[$parsed['pass']] = strtolower($parsed['pass']);

            $dsn = str_replace($parsed['pass'], '%s', $dsn);
        }

        if ($parsed['host'] !== 'default') {
            $options[$parsed['host']] = strtolower($parsed['host']);
            $dsn = str_replace($parsed['host'], '%s', $dsn);
        }

        if (isset($parsed['path'])) {
            $path = ltrim($parsed['path'], '/');
            $options[$path] = strtolower($path);
            $dsn = str_replace($path, '%s', $dsn);
        }

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query);

            foreach ($query as $value) {
                $dsn = str_replace($value, '%s', $dsn);

                $options[$value] = strtolower($value);
            }
        }

        return [$dsn, $options];
    }
}
