<?php

declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector\DTOs;

/**
 * @public This class is part of the public API and may be used in clients.
 */
final class ParameterMap
{
    public function __construct(
        public readonly string $parameter,
        public readonly string $class,
        public readonly mixed $value,
    ) {
    }
}
