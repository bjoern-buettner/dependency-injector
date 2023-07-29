<?php
declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector;

use ReflectionParameter;

interface TypeFactory
{
    public function parameter(ReflectionParameter $parameter): ParameterType;
}