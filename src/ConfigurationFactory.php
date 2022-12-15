<?php

declare(strict_types=1);

namespace GuidoFaecke\MezzioDoctrineOdm;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Types\Type;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;

/** @method Configuration __invoke(ContainerInterface $container) */
class ConfigurationFactory extends AbstractFactory
{
    /** {@inheritdoc} */
    protected function createWithConfig(ContainerInterface $container, string $configKey): mixed
    {
        $config = $this->retrieveConfig($container, $configKey, 'configuration');

        $configuration = new Configuration();

        // proxies
        $configuration->setProxyDir($config['proxy_dir']);
        $configuration->setProxyNamespace($config['proxy_namespace']);
        $configuration->setAutoGenerateProxyClasses($config['generate_proxies']);

        // hydrators
        $configuration->setAutoGenerateHydratorClasses($config['generate_hydrators']);
        $configuration->setHydratorDir($config['hydrator_dir']);
        $configuration->setHydratorNamespace($config['hydrator_namespace']);

        // persistent collections
        $configuration->setAutoGeneratePersistentCollectionClasses($config['generate_persistent_collections']);
        $configuration->setPersistentCollectionDir($config['persistent_collection_dir']);
        $configuration->setPersistentCollectionNamespace($config['persistent_collection_namespace']);

        if (isset($config['persistent_collection_factory'])) {
            $configuration->setPersistentCollectionFactory($container->get($config['persistent_collection_factory']));
        }

        if (isset($config['persistent_collection_generator'])) {
            $configuration->setPersistentCollectionGenerator(
                $container->get($config['persistent_collection_generator']),
            );
        }

        // default db
        if (isset($config['default_db'])) {
            $configuration->setDefaultDB($config['default_db']);
        }

        // caching
        $configuration->setMetadataCacheImpl(
            $this->retrieveDependency(
                $container,
                $config['metadata_cache'],
                'cache',
                CacheFactory::class,
            ),
        );

        // Register filters
        foreach ($config['filters'] as $alias => $class) {
            $configuration->addFilter($alias, $class);
        }

        // the driver
        $configuration->setMetadataDriverImpl(
            $this->retrieveDependency(
                $container,
                $config['driver'],
                'driver',
                DriverFactory::class,
            ),
        );

        // metadataFactory, if set
        if ($config['class_metadata_factory_name'] !== null) {
            $configuration->setClassMetadataFactoryName($config['class_metadata_factory_name']);
        }

        // respositoryFactory, if set
        if ($config['repository_factory'] !== null) {
            $configuration->setRepositoryFactory($container->get($config['repository_factory']));
        }

        // custom types
        foreach ($config['types'] as $name => $class) {
            if (Type::hasType($name)) {
                Type::overrideType($name, $class);
            } else {
                Type::addType($name, $class);
            }
        }

        $configuration->setDefaultDocumentRepositoryClassName($config['default_document_repository_class_name']);

        return $configuration;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig(string $configKey): array
    {
        return [
            'metadata_cache' => 'array',
            'query_cache' => 'array',
            'result_cache' => 'array',
            'hydration_cache' => 'array',
            'driver' => $configKey,
            'auto_generate_proxy_classes' => true,
            'proxy_dir' => 'data/cache/DoctrineEntityProxy',
            'proxy_namespace' => 'DoctrineEntityProxy',
            'entity_namespaces' => [],
            'datetime_functions' => [],
            'string_functions' => [],
            'numeric_functions' => [],
            'filters' => [],
            'named_queries' => [],
            'named_native_queries' => [],
            'custom_hydration_modes' => [],
            'naming_strategy' => null,
            'quote_strategy' => null,
            'default_repository_class_name' => null,
            'repository_factory' => null,
            'class_metadata_factory_name' => null,
            'entity_listener_resolver' => null,
            'second_level_cache' => [
                'enabled' => false,
                'default_lifetime' => 3600,
                'default_lock_lifetime' => 60,
                'file_lock_region_directory' => '',
                'regions' => [],
            ],
            'sql_logger' => null,
            'middlewares' => [],
        ];
    }

    /** @param callable(CacheItemPoolInterface):void $setCacheOnConfiguration */
    private function processCacheImplementation(
        Configuration $configuration,
        Cache | CacheItemPoolInterface $cache,
        callable $setCacheOnConfiguration,
    ): void {
        if ($cache instanceof Cache) {
            $cache = CacheAdapter::wrap($cache);
        }

        $setCacheOnConfiguration($cache);
    }
}
