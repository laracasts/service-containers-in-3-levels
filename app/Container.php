<?php

namespace App;

use Exception;
use Closure;
use ReflectionClass;

class Container
{
    /**
     * The list of registered bindings.
     */
    protected array $bindings = [];

    /**
     * The list of bound singletons.
     */
    protected array $singletons = [];

    /**
     * Bind into the container.
     */
    public function bind(string $key, string|callable $concrete, bool $shared = false): void
    {
        $this->bindings[$key] = [
            'concrete' => $concrete,
            'shared' => $shared
        ];
    }

    /**
     * Bind a singleton into the container.
     */
    public function singleton(string $key, string|callable $concrete): void
    {
        $this->bind($key, $concrete, true);
    }

    /**
     * Resolve a value out of the container.
     *
     * @throws Exception
     */
    public function get(string $key): mixed
    {
        if (!isset($this->bindings[$key])) {
            if (class_exists($key)) {
                return $this->build($key);
            }

            throw new Exception("No binding was registered for {$key}");
        }

        $binding = $this->bindings[$key];

        if ($binding['shared'] && isset($this->singletons[$key])) {
            return $this->singletons[$key];
        }

        if (!$binding['concrete'] instanceof Closure) {
            return $binding['concrete'];
        }

        return tap($binding['concrete'](), function ($concrete) use ($binding, $key) {
            if ($binding['shared']) {
                $this->singletons[$key] = $concrete;
            }
        });
    }

    /**
     * Instantiate a class and its dependencies.
     *
     * @throws Exception
     */
    protected function build(string $class): mixed
    {
        $reflector = new ReflectionClass($class);

        if (!$constructor = $reflector->getConstructor()) {
            return new $class();
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();

                continue;
            }

            if (!$parameter->getType() || !class_exists($dependency = $parameter->getType()?->getName())) {
                $message = "No binding was registered on {$class} for constructor parameter, \${$parameter->getName()}.";

                throw new Exception($message);
            }

            $dependencies[] = $this->build($dependency);
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
