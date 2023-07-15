<?php

declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector\DTOs;

use Me\BjoernBuettner\DependencyInjector\Mapping;


/**
 * @public This class is part of the public API and may be used in clients.
 */
final class InterfaceMap implements Mapping
{
    public function __construct(
        public readonly string $interface,
        public readonly string $implementation,
    ) {
    }
}
