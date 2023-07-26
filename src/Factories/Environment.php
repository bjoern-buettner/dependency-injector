<?php
declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector\Factories;

use Me\BjoernBuettner\DependencyInjector\EnvironmentAccess;
use Me\BjoernBuettner\DependencyInjector\Exceptions\InvalidEnvironment;
use Me\BjoernBuettner\DependencyInjector\Exceptions\NotInEnvironment;
use Symfony\Component\String\UnicodeString;

class Environment implements EnvironmentAccess
{
    /**
     * @var array<string, string>
     */
    private array $environment = [];

    public function __construct(?array $environment = null)
    {
        foreach (($environment ?? $_ENV) as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                throw new InvalidEnvironment("Environment must be an array of string keys and values.");
            }
            if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
                throw new InvalidEnvironment("Environment key $key must be upper snake case.");
            }
            $this->environment[(new UnicodeString($key))->lower()->camel()->toString()] = $value;
        }
    }
    public function get(?string $type, string ...$keys): mixed
    {
        if (count($keys) === 0) {
            throw new NotInEnvironment("No keys given.");
        }
        foreach ($keys as $name) {
            try {
                return $this->resolve($name, $type);
            } catch (NotInEnvironment) {
                // ignore
            }
        }
        $keyString = implode('|', $keys);
        throw new NotInEnvironment("None of the keys {$keyString} found in environment.");
    }

    private function resolve(string $name, ?string $type): mixed
    {
        if (isset($this->environment[$name])) {
            return match ($type) {
                'int' => (int)$this->environment[$name],
                'float' => (float)$this->environment[$name],
                'bool' => $this->environment[$name] === 'true',
                'array' => explode(',', $this->environment[$name]),
                default => (string)$this->environment[$name],
            };
        }
        throw new NotInEnvironment("{$name} not found in environment");
    }
}