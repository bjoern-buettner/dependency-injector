<?php

declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector;

use Me\BjoernBuettner\DependencyInjector\DTOs\FactoryMap;
use Me\BjoernBuettner\DependencyInjector\DTOs\InterfaceMap;
use Me\BjoernBuettner\DependencyInjector\DTOs\IntersectionMap;
use Me\BjoernBuettner\DependencyInjector\DTOs\ParameterMap;
use Me\BjoernBuettner\DependencyInjector\Exceptions\InvalidEnvironment;
use Me\BjoernBuettner\DependencyInjector\Exceptions\UninstanciableClass;
use Me\BjoernBuettner\DependencyInjector\Exceptions\UninvokableMethod;
use Me\BjoernBuettner\DependencyInjector\Exceptions\UnresolvableClass;
use Me\BjoernBuettner\DependencyInjector\Exceptions\UnresolvableMap;
use Me\BjoernBuettner\DependencyInjector\Exceptions\UnresolvableMethod;
use Me\BjoernBuettner\DependencyInjector\Exceptions\UnresolvableParameter;
use Me\BjoernBuettner\DependencyInjector\Exceptions\UnresolvableRecursion;
use Me\BjoernBuettner\DependencyInjector\Factories\Environment;
use Me\BjoernBuettner\DependencyInjector\Factories\Reflection;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Symfony\Component\String\UnicodeString;

/**
 * @public This class is the entry point of the dependency injection container.
 */
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
    private EnvironmentAccess $environment;
    /**
     * @var array<string, string>
     */
    private array $intersections = [];
    private ReflectionFactory $reflectionFactory;

    /**
     * @param array<int, string> $environment
     * @throws InvalidEnvironment
     * @throws UnresolvableClass
     */
    public function __construct(
        ?array $environment = null,
        bool $validateOnConstruct = false,
        ?ReflectionFactory $reflectionFactory = null,
        ParameterMap|InterfaceMap|FactoryMap|IntersectionMap ...$maps,
    ) {
        $this->reflectionFactory = $reflectionFactory ?? new Reflection();
        $this->environment = new Environment($environment);
        foreach ($maps as $map) {
            if ($map instanceof ParameterMap) {
                if ($validateOnConstruct && !class_exists($map->class)) {
                    throw new UnresolvableClass("Class {$map->class} does not exist.");
                }
                $this->parameters[$map->class . '.' . $map->parameter] = $map->value;
                continue;
            }
            if ($map instanceof InterfaceMap) {
                if ($validateOnConstruct && !class_exists($map->implementation)) {
                    throw new UnresolvableClass("Class {$map->implementation} does not exist.");
                }
                $this->interfaces[$map->interface] = $map->implementation;
                continue;
            }
            if ($map instanceof FactoryMap) {
                if ($validateOnConstruct && !class_exists($map->factory)) {
                    throw new UnresolvableClass("Class {$map->factory} does not exist.");
                }
                $this->factories[$map->created] = [$map->factory, $map->method];
                continue;
            }
            if ($map instanceof IntersectionMap) {
                if ($validateOnConstruct && !class_exists($map->className)) {
                    throw new UnresolvableClass("Class {$map->className} does not exist.");
                }
                $this->intersections[implode('&', $map->intersectionMap)] = $map->className;
                continue;
            }
            throw new UnresolvableMap('Unknown map type ' . get_class($map));
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
            if ($type instanceof ReflectionUnionType) {
                foreach ($type->getTypes() as $unionType) {
                    if ($unionType instanceof ReflectionNamedType) {
                        if (!$unionType->isBuiltin()) {
                            try {
                                return $this->build($unionType->getName());
                            } catch (UnresolvableClass) {
                            }
                        }
                    }
                }
                foreach ($type->getTypes() as $unionType) {
                    if ($unionType instanceof ReflectionNamedType) {
                        if ($unionType->isBuiltin()) {
                            foreach ([$key, $param->getName()] as $name) {
                                try {
                                    return $this->getEnvironment($name, $unionType->getName());
                                } catch (NotInEnvironment) {
                                    // ignore
                                }
                            }
                        }
                    }
                }
                foreach ($type->getTypes() as $unionType) {
                    if ($unionType instanceof ReflectionNamedType) {
                        if ($unionType->allowsNull()) {
                            return null;
                        }
                    }
                }
                if ($param->isDefaultValueAvailable()) {
                    return $param->getDefaultValue();
                }
                throw new UnresolvableParameter("Cannot resolve parameter {$param->getName()}.");
            }
            if ($type instanceof ReflectionIntersectionType) {
                $names = [];
                foreach ($type->getTypes() as $intersectionType) {
                    if ($intersectionType instanceof ReflectionNamedType) {
                        $names[] = $intersectionType->getName();
                    }
                }
                sort($names, SORT_STRING);
                $key = implode('&', $names);
                if (isset($this->intersections[$key])) {
                    return $this->build($this->intersections[$key]);
                }
                foreach ($type->getTypes() as $intersectionType) {
                    if ($intersectionType instanceof ReflectionNamedType) {
                        $object = $this->build($intersectionType->getName());
                        foreach ($names as $name) {
                            if (!($object instanceof $name)) {
                                continue 2;
                            }
                        }
                        return $object;
                    }
                }
                foreach ($type->getTypes() as $intersectionType) {
                    if ($intersectionType instanceof ReflectionNamedType) {
                        if ($intersectionType->allowsNull()) {
                            return null;
                        }
                    }
                }
                throw new UnresolvableParameter("Cannot resolve parameter {$param->getName()}.");
            }
            if (!$type->isBuiltin()) {
                return $this->build($type->getName());
            }
            try {
                return $this->environment->get($type->getName(), $key, $param->getName());
            } catch (NotInEnvironment) {
                // ignore
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
            $rc = $this->reflectionFactory->class($class);
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
            $rm = $this->reflectionFactory->method($object, $method);
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
