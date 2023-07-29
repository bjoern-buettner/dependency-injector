<?php
declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector\ParameterType;

use Me\BjoernBuettner\DependencyInjector\ParameterType;
use ReflectionParameter;

abstract class Base implements ParameterType
{
    private bool $nullable;
    private string $name;
    private bool $hasDefault;
    private mixed $default;
    private array $classes;

    public function __construct(ReflectionParameter $parameter, array $classes, private readonly string $basicType, private readonly bool $mustImplementAll)
    {
        $this->name = $parameter->getName();
        $this->nullable = $parameter->allowsNull();
        $this->hasDefault = $parameter->isDefaultValueAvailable();
        $this->default = $this->hasDefault ? $parameter->getDefaultValue() : null;
        $classes = array_unique($classes);
        sort($classes, SORT_STRING);
        $this->classes = $classes;
    }
    public function isNullable(): bool
    {
        return $this->nullable;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function hasDefault(): bool
    {
        return $this->hasDefault;
    }
    public function getDefault(): mixed
    {
        return $this->default;
    }
    public function areRequirementsMet(mixed $value): bool
    {
        if ($this->nullable && $value === null) {
            return true;
        }
        if ($this->mustImplementAll) {
            foreach ($this->classes as $class) {
                if (!is_a($value, $class)) {
                    return false;
                }
            }
            return true;
        }
        foreach ($this->classes as $class) {
            if ($value instanceof $class) {
                return true;
            }
        }
        return false;
    }
    public function getClasses(): array
    {
        return $this->classes;
    }
    public function getBasicType(): string
    {
        return $this->basicType;
    }
}