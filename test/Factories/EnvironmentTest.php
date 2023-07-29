<?php

namespace Me\BjoernBuettner\DependencyInjector\Factories;

use Me\BjoernBuettner\DependencyInjector\Exceptions\NotInEnvironment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Environment::class)]
class EnvironmentTest extends TestCase
{
    #[Test]
    public function getWithoutKeys(): void
    {
        $sut = new Environment([]);
        $this->expectException(NotInEnvironment::class);
        $this->expectExceptionMessage("No keys given.");
        $sut->get('string');
    }
    #[Test]
    public function getWithoutMissingKeys(): void
    {
        $sut = new Environment([]);
        $this->expectException(NotInEnvironment::class);
        $this->expectExceptionMessage("None of the keys key1|key2 found in environment.");
        $sut->get('string', 'key1', 'key2');
    }
    #[Test]
    public function getWithValidKey(): void
    {
        $sut = new Environment(['KEY1' => 'value1']);
        self::assertSame('value1', $sut->get(null, 'key2', 'key1'));
    }
    #[Test]
    public function getWithValidKeyAndIntCast(): void
    {
        $sut = new Environment(['KEY1' => '1234']);
        self::assertSame(1234, $sut->get('int', 'key2', 'key1'));
    }
    #[Test]
    public function getWithValidKeyAndFloatCast(): void
    {
        $sut = new Environment(['KEY1' => '123.4']);
        self::assertSame(123.4, $sut->get('float', 'key2', 'key1'));
    }
    #[Test]
    public function getWithValidKeyAndBoolCast(): void
    {
        $sut = new Environment(['KEY1' => 'true']);
        self::assertTrue($sut->get('bool', 'key2', 'key1'));
    }
    #[Test]
    public function getWithValidKeyAndArrayCast(): void
    {
        $sut = new Environment(['KEY1' => 'true,false,abc,xyz']);
        self::assertSame(['true','false','abc','xyz'], $sut->get('array', 'key2', 'key1'));
    }
    #[Test]
    public function getWithValidKeyAndEmptyElementAndArrayCast(): void
    {
        $sut = new Environment(['KEY1' => 'true,false,,abc,xyz']);
        self::assertSame(['true','false','abc','xyz'], $sut->get('array', 'key2', 'key1'));
    }
}
