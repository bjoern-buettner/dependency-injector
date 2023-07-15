<?php

declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use Symfony\Component\String\UnicodeString;

final class DependencyBuilder implements ContainerInterface
{
    /**
     * @var array<string, object>
     */
    private array $cache = [];

    /**
     * @var array<string, mixed>
     */
    private array $parameters = [];

    /**
     * @var array<string, string>
     */
    private array $interfaces = [];

    /**
     * @var array<string, string>
     */
    private array $factories = [];
    /**
     * @var array<int, bool>
     */
    private array $building = [];
    /**
     * @var array<int, string>
     */
    private array $environment = [];

    /**
     * @param array<int, string> $environment
     * @throws InvalidEnvironment
     * @throws UnresolvableClass
     */
    public function __construct(
        ?array $environment = null,
        bool $validateOnConstruct = false,
        Mapping ...$maps,
    ) {
        foreach (($environment ?? $_ENV) as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                throw new InvalidEnvironment("Environment must be an array of string keys and values.");
            }
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $key)) {
                throw new InvalidEnvironment("Environment key $key must be snake case.");
            }
            $this->environment[(new UnicodeString($key))->lower()->camel()->toString()] = $value;
        }
        foreach ($maps as $map) {
            if ($map instanceof ParameterMap) {
                if ($validateOnConstruct && !class_exists($map->class)) {
                    throw new UnresolvableClass("Class {$map->class} does not exist.");
                }
                $this->parameters[$map->class . '.' . $map->parameter] = $map->value;
            }
            if ($map instanceof InterfaceMap) {
                if ($validateOnConstruct && !class_exists($map->implementation)) {
                    throw new UnresolvableClass("Class {$map->implementation} does not exist.");
                }
                $this->interfaces[$map->interface] = $map->implementation;
            }
            if ($map instanceof FactoryMap) {
                if ($validateOnConstruct && !class_exists($map->factory)) {
                    throw new UnresolvableClass("Class {$map->factory} does not exist.");
                }
                $this->factories[$map->created] = [$map->factory, $map->method];
            }
        }
    }

    /**
     * @throws UnresolvableParameter
     * @throws UnresolvableClass
     * @throws UninstanciableClass
     * @throws UnresolvableRecursion
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
            if (isset($this->environment[$key])) {
                return match ($param->getType()->getName()) {
                    'int' => (int)$this->environment[$key],
                    'float' => (float)$this->environment[$key],
                    'bool' => $this->environment[$key] === 'true',
                    'array' => explode(',', $this->environment[$key]),
                    default => (string)$this->environment[$key],
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
     * @throws UnresolvableParameter
     * @throws UnresolvableClass
     * @throws UninstanciableClass
     * @throws UnresolvableRecursion
     */
    public function build(string $class): object
    {
        if (isset($this->building[$class])) {
            $this->building = [];
            throw new UnresolvableRecursion("Class $class depends on itself.");
        }
        if (isset($this->cache[$class])) {
            return $this->cache[$class];
        }
        if (isset($this->factories[$class])) {
            return $this->cache[$class] = $this->call($this->factories[$class][0], $this->factories[$class][1]);
        }
        if (isset($this->interfaces[$class])) {
            return $this->cache[$class] = $this->build($this->interfaces[$class]);
        }
        try {
            $rc = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new UnresolvableClass("Cannot resolve class $class.", 0, $e);
        } finally {
            if (isset($this->building[$class])) {
                unset($this->building[$class]);
            }
        }
        $constructor = $rc->getConstructor();
        if (!$constructor) {
            try {
                return $this->cache[$class] = $rc->newInstance();
            } catch (ReflectionException $e) {
                throw new UninstanciableClass("Cannot instantiate class $class.", 0, $e);
            } finally {
                if (isset($this->building[$class])) {
                    unset($this->building[$class]);
                }
            }
        }
        $params = $constructor->getParameters();
        $this->building[$class] = true;
        $args = [];
        foreach ($params as $param) {
            $args[] = $this->getParamValue($param, $this->parameters, $class . '.' . $param->getName());
        }
        unset($this->building[$class]);
        try {
            return $this->cache[$class] = $rc->newInstanceArgs($args);
        } catch (ReflectionException $e) {
            throw new UninstanciableClass("Cannot instantiate class $class.", 0, $e);
        }
    }

    /**
     * @param array<string, string> $variables
     * @throws UnresolvableParameter
     * @throws UnresolvableMethod
     * @throws UninvokableMethod
     * @throws UnresolvableClass
     * @throws UninstanciableClass
     * @throws UnresolvableRecursion
     */
    public function call(string $class, string $method, array $variables = []): mixed
    {
        $object = $this->build($class);
        try {
            $rm = new ReflectionMethod($object, $method);
        } catch (ReflectionException $e) {
            throw new UnresolvableMethod("Cannot resolve method $class::$method.", 0, $e);
        }
        $params = $rm->getParameters();
        $args = [];
        foreach ($params as $param) {
            $args[] = $this->getParamValue($param, $variables, $param->getName());
        }
        try {
            return $rm->invokeArgs($object, $args);
        } catch (ReflectionException $e) {
            throw new UninvokableMethod("Cannot invoke method $class::$method.", 0, $e);
        }
    }

    /**
     * @throws UnresolvableParameter
     * @throws UnresolvableClass
     * @throws UninstanciableClass
     * @throws UnresolvableRecursion
     */
    public function get(string $id): object
    {
        return $this->build($id);
    }

    public function has(string $id): bool
    {
        return class_exists($id) || isset($this->factories[$id]) || isset($this->interfaces[$id]);
    }
}
