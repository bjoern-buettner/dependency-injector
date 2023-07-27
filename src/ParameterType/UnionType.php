<?php
declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector\ParameterType;

use ReflectionParameter;

final class UnionType extends Base
{
    public function __construct(ReflectionParameter $parameter) {
        $classes = [];
        foreach ($parameter->getType()->getTypes() as $type) {
            $classes[] = $type->getName();
        }
        parent::__construct($parameter, $classes, 'object', false);
    }
}