<?php
declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use UnexpectedValueException;

final class UnresolvableParameter extends UnexpectedValueException implements ContainerExceptionInterface
{
}