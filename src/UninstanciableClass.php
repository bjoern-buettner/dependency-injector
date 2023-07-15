<?php
declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;

class UninstanciableClass extends InvalidArgumentException implements ContainerExceptionInterface
{
}