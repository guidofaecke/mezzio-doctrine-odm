# mezzio-doctrine-odm: Doctrine ODM Factories for Mezzio (PSR-11 Containers)

[Doctrine](https://github.com/doctrine) factories for Mezzio [PSR-11 containers](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-11-container.md).

This package provides a set of factories to be used with containers using the PSR-11 standard for an easy
Doctrine ODM (Mongo) integration in a project. This project is based on the work of
[@Roave](https://github.com/Roave/psr-container-doctrine).

## Installation

The easiest way to install this package is through composer:

```bash
$ composer require guidofaecke/mezzio-doctrine-odm
```

## Configuration

In the general case where you are only using a single connection, it's enough to define the entity manager factory:

```php
return [
    'dependencies' => [
        'factories' => [
            DocumentManager::class => \GuidoFaecke\MezzioDoctrineOdm\DocumentManagerFactory::class,
        ],
    ],
];
```

Each factory supplied by this package will by default look for a registered factory in the container. If it cannot find
one, it will automatically pull its dependencies from on-the-fly created factories. This saves you the hassle of
registering factories in your container which you may not need at all. Of course, you can always register those
factories when required. The following additional factories are available:

- ```\GuidoFaecke\PsrContainerDoctrine\CacheFactory``` (doctrine.cache.*)
- ```\GuidoFaecke\MezzioDoctrineOdm\ConnectionFactory``` (doctrine.connection.*)
- ```\GuidoFaecke\MezzioDoctrineOdm\ConfigurationFactory``` (doctrine.configuration.*)
- ```\GuidoFaecke\MezzioDoctrineOdm\DriverFactory``` (doctrine.driver.*)

Each of those factories supports the same static behavior as the entity manager factory. For container specific
configurations, there are a few examples provided in the example directory:

- [Aura.Di](example/aura-di.php)
- [PimpleInterop](example/pimple-interop.php)
- [Laminas\ServiceManager](example/laminas-servicemanager.php)

