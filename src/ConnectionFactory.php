<?php

declare(strict_types=1);

namespace GuidoFaecke\MezzioDoctrineOdm;

use MongoDB\Client;
use Psr\Container\ContainerInterface;

use function str_replace;
use function strpos;
use function substr;

use const PHP_INT_MAX;

/** @method Client __invoke(ContainerInterface $container) */
class ConnectionFactory extends AbstractFactory
{
    protected function createWithConfig(ContainerInterface $container, string $configKey): mixed
    {
        $config = $this->retrieveConfig($container, $configKey, 'connection');

        $connectionString = $config['connection_string'];

        $configuration = $this->retrieveDependency(
            $container,
            $config['configuration'],
            'configuration',
            ConfigurationFactory::class,
        );

        $dbName = $configuration->getDefaultDB();

        if ($connectionString === null) {
            $connectionString = 'mongodb://';

            $user     = $config['user'];
            $password = $config['password'];
            $dbName ??= $config['dbname'];

            if ($user !== null && $password !== null) {
                $connectionString .= $user . ':' . $password . '@';
            }

            $connectionString .= $config['server'] . ':' . $config['port'];

            if ($dbName !== null) {
                $connectionString .= '/' . $dbName;
            }
        } else {
            $dbName = $this->extractDatabaseFromConnectionString($connectionString);
        }

        // Set defaultDB to $dbName, if it's not defined in configuration
        if ($dbName !== null && $configuration->getDefaultDB() === null) {
            $configuration->setDefaultDB($dbName);
        }

        $driverOptions            = [];
        $driverOptions['typeMap'] = ['root' => 'array', 'document' => 'array'];

        return new Client(
            $connectionString,
            $config['uri_options'] ?? [],
            $driverOptions,
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig(string $configKey): array
    {
        return [
            'server' => 'localhost',
            'port' => '27017',
            'user' => null,
            'password' => null,
            'dbname' => null,
            'connection_string' => null,
            'uri_options' => [],
            'configuration' => $configKey,
        ];
    }

    private function extractDatabaseFromConnectionString(string $connectionString): ?string
    {
        $connectionString = str_replace('mongodb://', '', $connectionString);
        $dbStart          = strpos($connectionString, '/');

        if ($dbStart === false) {
            return null;
        }

        $dbEnd = strpos($connectionString, '?');

        return substr(
            $connectionString,
            $dbStart + 1,
            $dbEnd !== false ? ($dbEnd - $dbStart - 1) : PHP_INT_MAX,
        );
    }
}
