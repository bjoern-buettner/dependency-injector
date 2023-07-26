<?php

namespace Me\BjoernBuettner\DependencyInjector;

interface ParameterType
{
    public function areRequirementsMet(mixed $value): bool;
    public function getName(): string;
    public function getClasses(): array;
    public function getBasicType(): string;
    public function isNullable(): bool;
    public function hasDefault(): bool;
    public function getDefault(): mixed;
}