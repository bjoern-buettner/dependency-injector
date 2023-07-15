<?php

namespace Me\BjoernBuettner\DependencyInjector\DTOs;

use Me\BjoernBuettner\DependencyInjector\Mapping;

final class ParameterMap implements Mapping
{
    public function __construct(
        public readonly string $parameter,
        public readonly string $class,
        public readonly mixed $value,
    ) {
    }
}
