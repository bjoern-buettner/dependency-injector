<?php

namespace Me\BjoernBuettner\DependencyInjector;

final class FactoryMap implements Mapping
{
    public function __construct(
        public readonly string $created,
        public readonly string $factory,
        public readonly string $method,
    ) {
    }
}
