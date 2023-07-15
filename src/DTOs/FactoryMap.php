<?php

namespace Me\BjoernBuettner\DependencyInjector\DTOs;

use Me\BjoernBuettner\DependencyInjector\Mapping;

/**
 * @public This class is part of the public API and may be used in clients.
 */
final class FactoryMap implements Mapping
{
    public function __construct(
        public readonly string $created,
        public readonly string $factory,
        public readonly string $method,
    ) {
    }
}
