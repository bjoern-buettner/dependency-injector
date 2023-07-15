<?php
declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use UnexpectedValueException;

class UnresolvableParameter extends UnexpectedValueException implements ContainerExceptionInterface
{
}