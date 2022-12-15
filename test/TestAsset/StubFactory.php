<?php

declare(strict_types=1);

namespace GuidoFaeckeTest\MezzioDoctrineOdm\TestAsset;

use GuidoFaecke\MezzioDoctrineOdm\AbstractFactory;
use Psr\Container\ContainerInterface;

class StubFactory extends AbstractFactory
{
    /**
     * {@inheritdoc}
     */
    protected function createWithConfig(ContainerInterface $container, string $configKey): mixed
    {
        return $configKey;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveConfig(ContainerInterface $container, string $configKey, string $section): array
    {
        return parent::retrieveConfig($container, $configKey, $section);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig(string $configKey): array
    {
        return [];
    }
}
