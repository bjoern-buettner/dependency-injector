<?php

namespace Me\BjoernBuettner\DependencyInjector\DTOs;

use Me\BjoernBuettner\DependencyInjector\Mapping;

final class InterfaceMap implements Mapping
{
    public function __construct(
        public readonly string $interface,
        public readonly string $implementation,
    ) {
    }
}
