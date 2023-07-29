<?php

declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector;

use Me\BjoernBuettner\DependencyInjector\DTOs\FactoryMap;
use Me\BjoernBuettner\DependencyInjector\DTOs\InterfaceMap;
use Me\BjoernBuettner\DependencyInjector\DTOs\IntersectionMap;
use Me\BjoernBuettner\DependencyInjector\DTOs\ParameterMap;
use Me\BjoernBuettner\DependencyInjector\Exceptions\InvalidEnvironment;
use Me\BjoernBuettner\DependencyInjector\Exceptions\NotInEnvironment;
use Me\BjoernBuettner\DependencyInjector\Exceptions\UninstanciableClass;
use Me\BjoernBuettner\DependencyInjector\Exceptions\UninvokableMethod;
use Me\BjoernBuettner\DependencyInjector\Exceptions\UnresolvableClass;
use Me\BjoernBuettner\DependencyInjector\Exceptions\UnresolvableMethod;
use Me\BjoernBuettner\DependencyInjector\Exceptions\UnresolvableParameter;
use Me\BjoernBuettner\DependencyInjector\Exceptions\UnresolvableRecursion;
use Me\BjoernBuettner\DependencyInjector\Factories\Environment;
use Me\BjoernBuettner\DependencyInjector\Factories\Reflection;
use Me\BjoernBuettner\DependencyInjector\Factories\Type;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionException;
use ReflectionParameter;

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
    private TypeFactory $typeFactory;
    private LoggerInterface $logger;

    /**
     * @param array<int, string> $environment
     * @throws InvalidEnvironment
     * @throws UnresolvableClass
     */
    public function __construct(
        ?array $environment = null,
        ?ReflectionFactory $reflectionFactory = null,
        ?TypeFactory $typeFactory = null,
        LoggerInterface $logger = null,
        ParameterMap|InterfaceMap|FactoryMap|IntersectionMap ...$maps,
    ) {
        $this->typeFactory = $typeFactory ?? new Type();
        $this->reflectionFactory = $reflectionFactory ?? new Reflection();
        $this->logger = $logger ?? new NullLogger();
        $this->environment = new Environment($environment);
        foreach ($maps as $map) {
            if ($map instanceof ParameterMap) {
                $this->parameters[$map->class . '.' . $map->parameter] = $map->value;
            }
            if ($map instanceof InterfaceMap) {
                $this->interfaces[$map->interface] = $map->implementation;
            }
            if ($map instanceof FactoryMap) {
                $this->factories[$map->created] = [$map->factory, $map->method];
            }
            if ($map instanceof IntersectionMap) {
                $this->intersections[implode('&', $map->intersectionMap)] = $map->className;
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
        $parameter = $this->typeFactory->parameter($param);
        if ($classes = $parameter->getClasses()) {
            if (isset($this->intersections[implode('&', $classes)])) {
                return $this->build($this->intersections[implode('&', $classes)]);
            }
            foreach ($classes as $class) {
                try {
                    $object = $this->build($class);
                    if ($parameter->areRequirementsMet($object)) {
                        return $object;
                    }
                } catch (UnresolvableClass) {
                }
            }
        }
        if (!in_array($parameter->getBasicType(), ['object', 'callable', 'resource'], true)) {
            try {
                return $this->environment->get($parameter->getBasicType(), $key, $param->getName());
            } catch (NotInEnvironment) {
                // ignore
            }
        }
        if (!$parameter->hasDefault()) {
            return $parameter->getDefault();
        }
        if ($parameter->isNullable()) {
            return null;
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
        $this->building[$class] = true;
        $args = $this->getArguments($constructor->getParameters(), $this->parameters);
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
        $args = $this->getArguments($rm->getParameters(), $variables);
        try {
            return $rm->invokeArgs($object, $args);
        } catch (ReflectionException $e) {
            throw new UninvokableMethod("Cannot invoke method $class::$method.", 0, $e);
        }
    }

    /**
     * @param array<int, ReflectionParameter> $parameters
     * @param array<string, mixed> $variables
     * @return array<int, mixed>
     */
    private function getArguments(array $parameters, array $variables): array
    {
        $args = [];
        foreach ($parameters as $param) {
            $args[] = $this->getParamValue($param, $variables, $param->getName());
        }
        return $args;
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
