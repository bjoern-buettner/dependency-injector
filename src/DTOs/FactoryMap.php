<?php

declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector\DTOs;

/**
 * @public This class is part of the public API and may be used in clients.
 */
final class FactoryMap
{
    public function __construct(
        public readonly string $created,
        public readonly string $factory,
        public readonly string $method,
    ) {
    }
}
