<?php namespace TT\Engine;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */


use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionException;
use RuntimeException;

class Reflections
{

    private static Reflections $instance;


    /**
     * @param $className
     * @param $method
     * @param array $args
     * @return array
     * @throws ReflectionException
     */
    public static function methodParameters($className, $method, array $args = []): array
    {
        if (!$reflection = self::getMethod($className,$method)) {
            return $args;
        }

        foreach ($reflection->getParameters() as $num => $param) {
            if ($param->getType() && !$param->isDefaultValueAvailable()) {
                array_splice($args, $num, 0,  [App::get($param->getType()->getName())]);
            }
        }
        return $args;
    }


    /**
     * @param $function
     * @param array $args
     * @return array
     * @throws ReflectionException
     */
    public static function functionParameters($function, array $args = []): array
    {
        $reflection = self::getFunction($function);


        foreach ($reflection->getParameters() as $num => $param) {
            if ($param->getType() && !$param->isDefaultValueAvailable()) {
                array_splice($args, $num, 0,  [App::get($param->getType()->getName())]);
            }
        }
        return $args;
    }


    /**
     * @param $class
     * @param string $method
     * @return ReflectionMethod|null
     */
    public static function getMethod($class, string $method): ?ReflectionMethod
    {
        if(class_exists($class) && method_exists($class,$method)) {
            return new ReflectionMethod($class,$method);
        }

        return null;

    }

    /**
     * @param $function
     * @return ReflectionFunction
     * @throws ReflectionException
     */
    public static function getFunction($function): ReflectionFunction
    {
        return new ReflectionFunction($function);
    }

    /**
     * @param string $className
     * @return ReflectionClass
     */
    public static function getClass(string $className): ReflectionClass
    {
        if (! class_exists($className)) {
            throw new InvalidArgumentException('Class ['.$className.'] not found');
        }

        $reflection = new ReflectionClass($className);

        if ($reflection->isAbstract()) {
            throw new InvalidArgumentException('Class ['.$className.'] is abstract');
        }

        return $reflection;
    }


    /**
     * @return self
     */
    public static function getInstance(): self
    {
        if(!self::$instance) {
            self::$instance = new static;
        }

        return self::$instance;
    }
}
