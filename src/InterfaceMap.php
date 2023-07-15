<?php

namespace Me\BjoernBuettner\DependencyInjector;

final class InterfaceMap implements Mapping
{
    public function __construct(
        public readonly string $interface,
        public readonly string $implementation,
    ) {
    }
}
