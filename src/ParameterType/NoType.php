<?php
declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector\ParameterType;

use ReflectionParameter;

final class NoType extends Base
{
    public function __construct(ReflectionParameter $parameter)
    {
        parent::__construct($parameter, [], 'mixed', false);
    }
}