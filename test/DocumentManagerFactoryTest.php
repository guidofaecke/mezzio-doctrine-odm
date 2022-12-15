<?php

declare(strict_types=1);

namespace GuidoFaeckeTest\MezzioDoctrineOdm;

use GuidoFaecke\MezzioDoctrineOdm\AbstractFactory;
use GuidoFaecke\MezzioDoctrineOdm\DocumentManagerFactory;
use PHPUnit\Framework\TestCase;

class DocumentManagerFactoryTest extends TestCase
{
    public function testExtendsAbstractFactory(): void
    {
        self::assertInstanceOf(AbstractFactory::class, new DocumentManagerFactory());
    }
}
