<?php

namespace Me\BjoernBuettner\DependencyInjector\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(Reflection::class)]
class ReflectionTest extends TestCase
{
    public function testClass(): void
    {
        $sut = new Reflection();
        self::assertInstanceOf(ReflectionClass::class, $sut->class(TestCase::class));
    }
    public function testMethod(): void
    {
        $sut = new Reflection();
        self::assertInstanceOf(\ReflectionMethod::class, $sut->method($sut, 'class'));
    }
}
