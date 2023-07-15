<?php

declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector\Exceptions;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;

final class UnresolvableMethod extends InvalidArgumentException implements ContainerExceptionInterface
{
}