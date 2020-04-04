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

    private static $instance;


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
            if ($param->getClass() && !$param->isDefaultValueAvailable()) {
                array_splice($args, $num, 0,  [App::get($param->getClass()->name)]);                
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
            if ($param->getClass() && !$param->isDefaultValueAvailable()) {
                array_splice($args, $num, 0,  [App::get($param->getClass()->name)]);
            }
        }
        return $args;
    }


    /**
     * @param $class
     * @param string $method
     * @return ReflectionMethod|null
     * @throws ReflectionException
     */
    public static function getMethod($class, string $method): ?ReflectionMethod
    {
        if(class_exists($class) && method_exists($class,$method)) {
            return new ReflectionMethod($class,$method);
        }

        return null;

        //throw new RuntimeException(
        //    sprintf('class [%s] or method [%s] not found',$class,$method)
        //);

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
     * @throws ReflectionException
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
