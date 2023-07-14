<?php

declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use RuntimeException;
use Symfony\Component\String\UnicodeString;

class DependencyBuilder
{
    /**
     * @var array<string, object>
     */
    private array $cache = [];

    /**
     * @param array<string, mixed> $params
     * @param array<string, string> $interfaces
     * @param array<string, string> $factories
     */
    public function __construct(
        private readonly array $params = [],
        private readonly array $interfaces = [],
        private readonly array $factories = [],
    ) {
    }

    /**
     * @throws ReflectionException
     */
    private function getParamValue(ReflectionParameter $param, array $variables, string $key): mixed
    {
        if (isset($variables[$key])) {
            return $variables[$key];
        }
        if ($type = $param->getType()) {
            if (!$type->isBuiltin()) {
                return $this->build($type->getName());
            }
            $envName = (new UnicodeString($param->getName()))->snake()->upper()->toString();
            if (isset($_ENV[$envName])) {
                return match ($param->getType()->getName()) {
                    'int' => (int)$_ENV[$envName],
                    'float' => (float)$_ENV[$envName],
                    'bool' => $_ENV[$envName] === 'true',
                    'array' => explode(',', $_ENV[$envName]),
                    default => (string)$_ENV[$envName],
                };
            }
            if ($type->allowsNull()) {
                return null;
            }
        }
        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }
        throw new UnresolvableParameter("Cannot resolve parameter {$param->getName()}.");
    }
    /**
     * @throws ReflectionException
     */
    public function build(string $class): object
    {
        if (isset($this->cache[$class])) {
            return $this->cache[$class];
        }
        if (isset($this->factories[$class])) {
            return $this->cache[$class] = $this->build($this->factories[$class])->get();
        }
        if (isset($this->interfaces[$class])) {
            return $this->cache[$class] = $this->build($this->interfaces[$class]);
        }
        $rc = new ReflectionClass($class);
        $constructor = $rc->getConstructor();
        if (!$constructor) {
            return $this->cache[$class] = $rc->newInstance();
        }
        $params = $constructor->getParameters();
        $args = [];
        foreach ($params as $param) {
            $args[] = $this->getParamValue($param, $this->params, $class . '.' . $param->getName());
        }
        return $this->cache[$class] = $rc->newInstanceArgs($args);
    }

    /**
     * @param array<string, string> $variables
     * @throws ReflectionException
     */
    public function call(string $class, string $method, array $variables = []): string
    {
        $object = $this->build($class);
        $rm = new ReflectionMethod($object, $method);
        $params = $rm->getParameters();
        $args = [];
        foreach ($params as $param) {
            $args[] = $this->getParamValue($param, $variables, $param->getName());
        }
        return $rm->invokeArgs($object, $args);
    }
}
