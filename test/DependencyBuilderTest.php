<?php

namespace Me\BjoernBuettner\DependencyInjector;

use Me\BjoernBuettner\DependencyInjector\DTOs\FactoryMap;
use Me\BjoernBuettner\DependencyInjector\DTOs\InterfaceMap;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DependencyBuilderTest extends TestCase
{
    public function testBuild(): void
    {
        $sut = new DependencyBuilder();
        self::markTestIncomplete('This test has not been implemented yet.');
    }
    public function testGet(): void
    {
        $sut = new DependencyBuilder();
        self::markTestIncomplete('This test has not been implemented yet.');
    }
    public function testHas(): void
    {
        $sut = new DependencyBuilder(
            [],
            false,
            null,
            new FactoryMap('YourClass', 'YourFactory', 'build'),
            new InterfaceMap('YourInterface', 'YourInterfaceImplementation')
        );
        self::assertTrue($sut->has(DependencyBuilder::class));
        self::assertFalse($sut->has(ContainerInterface::class));
        self::assertFalse($sut->has('NotExistingClass'));
        self::assertTrue($sut->has('YourClass'));
        self::assertTrue($sut->has('YourInterface'));
    }
    public function testCall(): void
    {
        $sut = new DependencyBuilder();
        self::markTestIncomplete('This test has not been implemented yet.');
    }
}
