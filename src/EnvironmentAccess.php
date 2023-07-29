<?php

namespace Me\BjoernBuettner\DependencyInjector;

interface EnvironmentAccess
{
    public function get(string $type, string ...$keys): mixed;
}