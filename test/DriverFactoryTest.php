<?php

declare(strict_types=1);

namespace GuidoFaeckeTest\MezzioDoctrineOdm;

use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\ODM\MongoDB\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\Persistence\Mapping\Driver\AnnotationDriver as AbstractAnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use GuidoFaecke\MezzioDoctrineOdm\DriverFactory;
use GuidoFaecke\MezzioDoctrineOdm\Exception\OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;

use function var_dump;

class DriverFactoryTest extends TestCase
{
    public function testMissingClassKeyWillReturnOutOfBoundException(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory   = new DriverFactory();

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Missing "class" config key');

        $factory($container);
    }

    public function testItSupportsGlobalBasenameOptionOnFileDrivers(): void
    {
        $globalBasename = 'foobar';

        $container = $this->createContainerMockWithConfig(
            [
                'doctrine' => [
                    'driver' => [
                        'odm_default' => [
                            'class' => TestAsset\StubFileDriver::class,
                            'global_basename' => $globalBasename,
                        ],
                    ],
                ],
            ]
        );

        $driver = (new DriverFactory())->__invoke($container);
        self::assertInstanceOf(FileDriver::class, $driver);
        self::assertSame($globalBasename, $driver->getGlobalBasename());
    }

    /**
     * @psalm-param class-string<FileDriver> $driverClass
     * @dataProvider simplifiedDriverClassProvider
     */
    public function testItSupportsSettingExtensionInDriversUsingSymfonyFileLocator(string $driverClass): void
    {
        $extension = '.foo.bar';

        $container = $this->createContainerMockWithConfig(
            [
                'doctrine' => [
                    'driver' => [
                        'odm_default' => [
                            'class' => $driverClass,
                            'extension' => $extension,
                        ],
                    ],
                ],
            ]
        );

        $driver = (new DriverFactory())->__invoke($container);
        self::assertInstanceOf(FileDriver::class, $driver);
        self::assertSame($extension, $driver->getLocator()->getFileExtension());
    }

    /**
     * @return string[][]
     *
     * @psalm-return list<list<class-string<FileDriver>>>
     */
    public function simplifiedDriverClassProvider(): array
    {
        return [
            [ SimplifiedXmlDriver::class ],
        ];
    }

    public function testMappingDriverChainIsCreatedWithNoDefaultDriverWhenDefaultDriverNotSpecified(): void
    {
        $container = $this->createContainerMockWithConfig(
            [
                'doctrine' => [
                    'driver' => [
                        'odm_default' => [
                            'class' => MappingDriverChain::class,
                        ],
                    ],
                ],
            ],
            1
        );

        $driver = (new DriverFactory())->__invoke($container);
        self::assertInstanceOf(MappingDriverChain::class, $driver);
        self::assertNull($driver->getDefaultDriver());
    }

    public function testItSupportsSettingDefaultDriverUsingMappingDriverChain(): void
    {
        $container = $this->createContainerMockWithConfig(
            [
                'doctrine' => [
                    'driver' => [
                        'odm_default' => [
                            'class' => MappingDriverChain::class,
                            'default_driver' => 'odm_stub',
                        ],
                        'odm_stub' => [
                            'class' => TestAsset\StubFileDriver::class,
                        ],
                    ],
                ],
            ],
            2
        );

        $driver = (new DriverFactory())->__invoke($container);
        self::assertInstanceOf(MappingDriverChain::class, $driver);
        self::assertInstanceOf(TestAsset\StubFileDriver::class, $driver->getDefaultDriver());
    }

    /**
     * @psalm-param class-string<AbstractAnnotationDriver> $driverClass
     * @dataProvider annotationDriverClassProvider
     */
    public function testItSupportsAnnotationDrivers(string $driverClass): void
    {
        $services  = [
            'config' => [
                'doctrine' => [
                    'driver' => [
                        'odm_default' => [
                            'class' => $driverClass,
                            'cache' => 'default',
                        ],
                    ],
                ],
            ],
            'doctrine.cache.default' => new ArrayCache(),
        ];
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            static function (string $id) use ($services): bool {
                return isset($services[$id]);
            }
        );
        $container->method('get')->willReturnCallback(
        /**
         * @return object|array
         */
            static function (string $id) use ($services) {
                return $services[$id];
            }
        );

        $driver = (new DriverFactory())->__invoke($container);
        self::assertInstanceOf($driverClass, $driver);
    }

    public function testCanProcessCacheItemPoolAnnotationReader(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->withConsecutive(['config'], ['doctrine.cache.psr'])
            ->willReturn(true);

        $container
            ->method('get')
            ->withConsecutive(['config'], ['doctrine.cache.psr'])
            ->willReturnOnConsecutiveCalls(
                [
                    'doctrine' => [
                        'driver' => [
                            'odm_default' => [
                                'class' => AnnotationDriver::class,
                            ],
                        ],
                    ],
                ],
                $this->createMock(CacheItemPoolInterface::class)
            );

        $driver = (new DriverFactory())->__invoke($container);
        self::assertInstanceOf(AnnotationDriver::class, $driver);
    }

    /**
     * @return string[][]
     *
     * @psalm-return list<list<class-string<AbstractAnnotationDriver>>>
     */
    public function annotationDriverClassProvider(): array
    {
        return [
            [ AttributeDriver::class ],
            [ AnnotationDriver::class ],
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createContainerMockWithConfig(array $config, int $expectedCalls = 1): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly($expectedCalls))->method('has')->with('config')->willReturn(true);
        $container->expects($this->exactly($expectedCalls))->method('get')->with('config')->willReturn($config);

        return $container;
    }
}
