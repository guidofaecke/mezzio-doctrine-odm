<?php

declare(strict_types=1);

namespace GuidoFaecke\MezzioDoctrineOdm;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\ChainCache;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\PhpFileCache;
use Doctrine\Common\Cache\PredisCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Cache\WinCacheCache;
use Doctrine\Common\Cache\ZendDataCache;
use GuidoFaecke\MezzioDoctrineOdm\Exception\OutOfBoundsException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;

use function array_key_exists;
use function array_map;
use function assert;
use function is_array;
use function is_string;

/** @method Cache|CacheItemPoolInterface __invoke(ContainerInterface $container) */
class CacheFactory extends AbstractFactory
{
    protected function createWithConfig(ContainerInterface $container, string $configKey): mixed
    {
        $config = $this->retrieveConfig($container, $configKey, 'cache');

        if (! array_key_exists('class', $config)) {
            throw OutOfBoundsException::forMissingConfigKey('class');
        }

        $instance = null;

        if (array_key_exists('instance', $config)) {
            $instance = is_string($config['instance']) ? $container->get($config['instance']) : $config['instance'];
        }

        switch ($config['class']) {
            case FilesystemCache::class:
            case PhpFileCache::class:
                $cache = new $config['class']($config['directory']);
                break;

            case PredisCache::class:
                assert($instance !== null);
                $cache = new PredisCache($instance);
                break;

            case ChainCache::class:
                $providers = array_map(
                    function ($provider) use ($container): CacheProvider {
                        return $this->createWithConfig($container, $provider);
                    },
                    is_array($config['providers']) ? $config['providers'] : [],
                );
                $cache     = new ChainCache($providers);
                break;

            default:
                $cache = $container->has($config['class']) ? $container->get($config['class']) : new $config['class']();
        }

        if ($cache instanceof MemcachedCache) {
            assert($instance !== null);
            $cache->setMemcached($instance);
        } elseif ($cache instanceof RedisCache) {
            assert($instance !== null);
            $cache->setRedis($instance);
        }

        if ($cache instanceof CacheProvider && array_key_exists('namespace', $config)) {
            $cache->setNamespace($config['namespace']);
        }

        return $cache;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig(string $configKey): array
    {
        switch ($configKey) {
            case 'apcu':
                return [
                    'class' => ApcuCache::class,
                    'namespace' => 'mezzio-doctrine-orm',
                ];

            case 'array':
                return [
                    'class' => ArrayCache::class,
                    'namespace' => 'mezzio-doctrine-orm',
                ];

            case 'filesystem':
                return [
                    'class' => FilesystemCache::class,
                    'directory' => 'data/cache/DoctrineCache',
                    'namespace' => 'mezzio-doctrine-orm',
                ];

            case 'memcached':
                return [
                    'class' => MemcachedCache::class,
                    'instance' => 'my_memcached_alias',
                    'namespace' => 'mezzio-doctrine-orm',
                ];

            case 'phpfile':
                return [
                    'class' => PhpFileCache::class,
                    'directory' => 'data/cache/DoctrineCache',
                    'namespace' => 'mezzio-doctrine-orm',
                ];

            case 'predis':
                return [
                    'class' => PredisCache::class,
                    'instance' => 'my_predis_alias',
                    'namespace' => 'mezzio-doctrine-orm',
                ];

            case 'redis':
                return [
                    'class' => RedisCache::class,
                    'instance' => 'my_redis_alias',
                    'namespace' => 'mezzio-doctrine-orm',
                ];

            case 'wincache':
                return [
                    'class' => WinCacheCache::class,
                    'namespace' => 'mezzio-doctrine-orm',
                ];

            case 'zenddata':
                return [
                    'class' => ZendDataCache::class,
                    'namespace' => 'mezzio-doctrine-orm',
                ];

            case 'chain':
                return [
                    'class' => ChainCache::class,
                    'namespace' => 'mezzio-doctrine-orm',
                    'providers' => [],
                ];

            default:
                return [];
        }
    }
}
