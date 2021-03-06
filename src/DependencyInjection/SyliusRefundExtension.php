<?php

declare(strict_types=1);

namespace Sylius\RefundPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\Application\Kernel;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SyliusRefundExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $config);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('services.xml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('doctrine_migrations') || !$container->hasExtension('sylius_labs_doctrine_migrations_extra')) {
            return;
        }

        $doctrineConfig = $container->getExtensionConfig('doctrine_migrations');

        if (!$this->shouldDoctrineMigrationsConfigurationBePrepended()) {
            return;
        }

        $migrationsPath = (array) \array_pop($doctrineConfig)['migrations_paths'];
        $container->prependExtensionConfig('doctrine_migrations', [
            'migrations_paths' => \array_merge(
                $migrationsPath ?? [],
                [
                    'Sylius\RefundPlugin\Migrations' => '@SyliusRefundPlugin/Migrations',
                ]
            ),
        ]);

        $container->prependExtensionConfig('sylius_labs_doctrine_migrations_extra', [
            'migrations' => [
                'Sylius\RefundPlugin\Migrations' => ['Sylius\Bundle\CoreBundle\Migrations'],
            ],
        ]);
    }

    private function shouldDoctrineMigrationsConfigurationBePrepended(): bool
    {
        // prepending configuration is required only for DoctrineMigrations ^3.0 used in Sylius ^1.8
        return (int) Kernel::MINOR_VERSION >= 8;
    }
}
