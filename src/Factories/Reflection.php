<?php
declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector\Factories;

use Me\BjoernBuettner\DependencyInjector\ReflectionFactory;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class Reflection implements ReflectionFactory
{
    /**
     * @throws ReflectionException
     */
    public function class(string $class): ReflectionClass
    {
        return new ReflectionClass($class);
    }

    /**
     * @throws ReflectionException
     */
    public function method(object $class, string $method): ReflectionMethod
    {
        return new ReflectionMethod($class, $method);
    }
}