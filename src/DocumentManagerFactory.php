<?php

declare(strict_types=1);

namespace GuidoFaecke\MezzioDoctrineOdm;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Container\ContainerInterface;

/** @method DocumentManager __invoke(ContainerInterface $container) */
class DocumentManagerFactory extends AbstractFactory
{
    protected function createWithConfig(ContainerInterface $container, string $configKey): mixed
    {
        $config = $this->retrieveConfig($container, $configKey, 'odm_manager');

        $connection = $this->retrieveDependency(
            $container,
            $config['connection'],
            'conection',
            ConnectionFactory::class,
        );

        $configuration = $this->retrieveDependency(
            $container,
            $config['configuration'],
            'configuration',
            ConfigurationFactory::class,
        );

//        $eventManager = $this->retrieveDependency(
//            $container,
//            $config['event_manager'],
//            'event_manager',
//            EventManager::class
//        );

        return DocumentManager::create(
            $connection,
            $configuration,
            // $eventManager,
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig(string $configKey): array
    {
        return [
            'connection' => $configKey,
            'configuration' => $configKey,
            'event_manager' => $configKey,
        ];
    }
}
