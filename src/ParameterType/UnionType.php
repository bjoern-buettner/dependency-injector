<?php
declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector\ParameterType;

use ReflectionParameter;

final class UnionType extends Base
{
    public function __construct(ReflectionParameter $parameter) {
        $classes = [];
        $builtins = [];
        foreach ($parameter->getType()->getTypes() as $type) {
            if (!$type->isBuiltin()) {
                $classes[] = $type->getName();
            } elseif (!in_array($type->getName(), ['object', 'callable', 'resource'], true)) {
                $builtins[] = $type->getName();
            }
        }
        $type = 'object';
        if (count($classes) === 0) {
            $type = 'mixed';
            if (in_array('string', $builtins, true)) {
                $type = 'string';
            } elseif (in_array('int', $builtins, true)) {
                $type = 'int';
            } elseif (in_array('float', $builtins, true)) {
                $type = 'float';
            } elseif (in_array('bool', $builtins, true)) {
                $type = 'bool';
            } elseif (in_array('array', $builtins, true)) {
                $type = 'array';
            }
        }
        parent::__construct($parameter, $classes, $type, false);
    }
}