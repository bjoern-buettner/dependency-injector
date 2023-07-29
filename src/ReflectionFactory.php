<?php

namespace Me\BjoernBuettner\DependencyInjector;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

interface ReflectionFactory
{
    /**
     * @throws ReflectionException
     */
    public function class(string $class): ReflectionClass;

    /**
     * @throws ReflectionException
     */
    public function method(object $class, string $method): ReflectionMethod;
}