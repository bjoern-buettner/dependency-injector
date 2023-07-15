<?php

namespace Me\BjoernBuettner\DependencyInjector\DTOs;

use Me\BjoernBuettner\DependencyInjector\Mapping;

/**
 * @public This class is part of the public API and may be used in clients.
 */
final class ParameterMap implements Mapping
{
    public function __construct(
        public readonly string $parameter,
        public readonly string $class,
        public readonly mixed $value,
    ) {
    }
}
