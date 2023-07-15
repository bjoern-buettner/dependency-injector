<?php

namespace Me\BjoernBuettner\DependencyInjector\DTOs;

use Me\BjoernBuettner\DependencyInjector\Mapping;

final class FactoryMap implements Mapping
{
    public function __construct(
        public readonly string $created,
        public readonly string $factory,
        public readonly string $method,
    ) {
    }
}
