<?php namespace TT\Engine;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */


use ReflectionMethod;
use ReflectionFunction;


class Reflections
{
    public static function classMethodParameters($className, $method, array $args = [])
    {
        if (!method_exists($className, $method)) {
            return $args;
        }
        

        $parameters = (new ReflectionMethod($className, $method))->getParameters();

        foreach ($parameters as $num => $param) {
            if ($param->getClass() && !$param->isDefaultValueAvailable()) {
                array_splice($args, $num, 0,  [App::get($param->getClass()->name)]);                
            }
        }
        return $args;
    }


    public static function functionParameters($function, array $args = [])
    {
        $parameters  = (new ReflectionFunction($function))->getParameters();

        foreach ($parameters as $num => $param) {
            if ($param->getClass() && !$param->isDefaultValueAvailable()) {
                array_splice($args, $num, 0,  [App::get($param->getClass()->name)]);
            }
        }
        return $args;
    }
}
