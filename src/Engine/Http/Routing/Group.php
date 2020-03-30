<?php

namespace TT\Engine\Http\Routing;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */

use Closure;

trait Group
{

    /**
     * @param $parameters
     * @param Closure $callback
     * @return null|mixed
     */
    public function group($parameters, Closure $callback)
    {
        $parameters = $this->mergeGroup($parameters);

        $callback($this);

        $this->restoreGroup(...$parameters);
    }


    /**
     * @param $parameters
     * @return array|string
     */
    private function mergeGroup($parameters)
    {
        if (is_string($parameters)) {

            $prefix = $parameters;

            $this->prefix .= trim($prefix);

            return [null, $prefix, null, null, null];
        }

        $prefix = $parameters['prefix'] ?? '';

        $_namespace = $parameters['namespace'] ?? null;

        $middleware = $parameters['middleware'] ?? null;

        $domain = $parameters['domain'] ?? null;

        if (isset($parameters['name'])) {
            $name = $parameters['name'];
            $this->name .= $name;
        }

        if ($domain) {
            $this->domain(trim($domain, '/'));
        }

        if ($middleware) {
            if (!is_array($middleware)) {
                $middleware = [$middleware];
            }

            $this->middleware = array_merge($this->middleware, $middleware);
        }

        if ($_namespace) {
            $namespace['old'] = $this->namespace;
            $namespace['new'] = $_namespace;
            $this->namespace = $namespace['new'];
        }

        $this->prefix .= trim($prefix);

        return [
            $middleware,
            $prefix,
            $name ?? null,
            $domain,
            $namespace ?? null
        ];
    }


    /**
     * @param $middleware
     * @param $prefix
     * @param $name
     * @param $domain
     * @param $namespace
     */
    private function restoreGroup(
        $middleware,
        $prefix,
        $name,
        $domain,
        $namespace
    ) {
        if ($middleware && !empty($middleware)) {
            $this->middleware = array_slice($this->middleware, 0, -count($middleware));
        }

        if ($namespace) {
            $this->namespace = $namespace['old'] ?? 'App\\Controllers';
        }

        if ($prefix && !empty(trim($prefix))) {
            $this->prefix = substr($this->prefix, 0, -strlen(trim($prefix)));
        }

        if ($name) {
            $this->name = substr($this->name, 0, -strlen($name));
            if (empty($this->name)) {
                $this->name = null;
            }
        }

        if ($domain) {
            $this->domain = null;
        }
    }
}
