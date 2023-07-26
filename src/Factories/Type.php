<?php
declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector\Factories;

use Me\BjoernBuettner\DependencyInjector\ParameterType;
use ReflectionParameter;

class Type
{
    public function parameter(ReflectionParameter $parameter): ParameterType\Base
    {
        if (!$parameter->hasType()) {
            return new ParameterType\NoType($parameter);
        }
        if ($parameter->getType() instanceof \ReflectionUnionType) {
            return new ParameterType\UnionType($parameter);
        }
        if ($parameter->getType() instanceof \ReflectionIntersectionType) {
            return new ParameterType\IntersectionType($parameter);
        }
        if ($parameter->getType()->isBuiltin()) {
            return new ParameterType\BuiltinType($parameter);
        }
        return new ParameterType\NamedType($parameter);
    }
}