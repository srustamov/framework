<?php

namespace TT\Engine\Http\Routing;

use ArrayAccess;
use InvalidArgumentException;


class Route implements ArrayAccess
{
    private $attributes = [
        'prefix' => null,
        'namespace' => null,
        'middleware' => [],
        'patterns' => [],
        'domain' => null,
        'name' => null,
        'callback' => null
    ];

    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }


    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setAttribute($name, $value): self
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @param $attributes
     * @return $this
     */
    public function setAttributes($attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getAttribute($name)
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }


    /**
     * @param $prefix
     * @return $this
     */
    public function prefix($prefix): self
    {
        $this->setAttribute('prefix', $prefix);

        return $this;
    }

    /**
     * @param $prefix
     * @return $this
     */
    public function prependPrefix($prefix): self
    {
        $this->prefix($prefix . $this->getAttribute('prefix'));

        return $this;
    }


    /**
     * @param $prefix
     * @return $this
     */
    public function appendPrefix($prefix): self
    {
        $this->prefix($this->getAttribute('prefix') . $prefix);

        return $this;
    }


    /**
     * @return mixed|null
     */
    public function getPrefix()
    {
        return $this->getAttribute('prefix');
    }


    /**
     * @param $namespace
     * @return $this
     */
    public function namespace($namespace): self
    {
        $this->setAttribute('namespace', $namespace);

        return $this;
    }

    /**
     * @param $namespace
     * @return $this
     */
    public function prependNamespace($namespace): self
    {
        $this->namespace(
            rtrim($namespace, '\\') . '\\' . (ltrim($this->getAttribute('namesapce') ?? '', '\\'))
        );

        return $this;
    }

    /**
     * @param $namespace
     * @return $this
     */
    public function appendNamespace($namespace): self
    {
        $this->namespace((trim($this->getAttribute('namespace') ?? '', '\\')) . '\\' . ltrim($namespace, '\\'));

        return $this;
    }


    /**
     * @return mixed|null
     */
    public function getNamespace()
    {
        return $this->getAttribute('namespace');
    }


    /**
     * @param $middleware
     * @return $this
     */
    public function middleware($middleware): self
    {
        if (is_array($middleware)) {
            $this->setAttribute(
                'middleware',
                array_merge($this->getAttribute('middleware'), $middleware)
            );
        } else {
            $this->attributes['middleware'][] = $middleware;
        }


        return $this;
    }


    /**
     * @return mixed|null
     */
    public function getMiddleware()
    {
        return $this->getAttribute('middleware');
    }


    /**
     * @param $domain
     * @return $this
     */
    public function domain($domain): self
    {
        $this->setAttribute('domain', $domain);

        return $this;
    }


    /**
     * @return mixed|null
     */
    public function getDomain()
    {
        return $this->getAttribute('domain');
    }


    /**
     * @param $pattern
     * @return $this
     */
    public function pattern($pattern): self
    {
        if (is_array($pattern)) {
            $this->setAttribute(
                'patterns',
                array_merge($this->getAttribute('patterns'), $pattern)
            );
        } else {
            $this->attributes['patterns'][] = $pattern;
        }

        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getPatterns()
    {
        return $this->getAttribute('patterns');
    }


    /**
     * @param string $name
     * @return $this
     */
    public function name($name): self
    {
        if (!$name || empty(trim($name))) {
            return $this;
        }
        if ($name) {
            $name = $this->getAttribute('name') . $name;
        }

        $this->setAttribute('name', $name);

        $this->router->routes['NAMES'][$name] = $this->getPrefix();

        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function prependName($name): self
    {
        $this->name($name . $this->getAttribute('name'));

        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function appendName($name): self
    {
        $this->name($this->getAttribute('name') . $name);

        return $this;
    }


    /**
     * @return mixed|null
     */
    public function getName()
    {
        return $this->getAttribute('name');
    }


    /**
     * @param $callback
     * @return $this
     */
    public function setCallback($callback): self
    {
        if (is_array($callback)) {
            $this->parseCallback($callback);
        } else {
            $this->setAttribute('callback', $callback);
        }

        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getCallback()
    {
        return $this->getAttribute('callback');
    }


    /**
     * @param $callback
     */
    protected function parseCallback($callback): void
    {
        if (
            isset($callback['uses']) &&
            is_string($callback['uses']) &&
            !empty(trim($callback))
        ) {
            $this->setAttribute('callback', $callback['uses']);
        } else {
            throw new InvalidArgumentException('Route bad callback');
        }

        if (isset($callback['as'])) {
            $this->name($callback['as']);
        }

        if (isset($callback['middleware'])) {
            $this->middleware($callback['middleware']);
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (strtolower($name) === 'group') {
            if (isset($arguments[0]) && is_callable($arguments[0])) {
                $attributes = array_filter($this->attributes, function ($value) {
                    return $value && !empty($value);
                });

                if ($this->getAttribute('name') && isset($this->router->routes['NAMES'][$this->getAttribute('name')])) {
                    unset($this->router->routes['NAMES'][$this->getAttribute('name')]);
                }
                return $this->router->group($attributes, $arguments[0]);
            }
            throw new InvalidArgumentException('Route group parameter is not callable');
        }
        return $this->router->setBuilder($this)->$name(...$arguments);


    }


    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        if (isset($this->attributes[$offset])) {
            unset($this->attributes[$offset]);
        }
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }
}
