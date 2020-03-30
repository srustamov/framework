<?php namespace TT\Engine\Http\Routing;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */



trait Methods
{
    /**
     * @param $path
     * @param $handler
     * @return mixed
     */
    public function get($path, $handler)
    {
        return $this->add(['GET'], $path, $handler);
    }

    /**
     * @param $path
     * @param $handler
     * @return mixed
     */
    public function post($path, $handler)
    {
        return $this->add(['POST'], $path, $handler);
    }

    /**
     * @param $path
     * @param $handler
     * @return mixed
     */
    public function put($path, $handler)
    {
        return $this->add(['PUT'], $path, $handler);
    }

    /**
     * @param $path
     * @param $handler
     * @return mixed
     */
    public function delete($path, $handler)
    {
        return $this->add(['DELETE'], $path, $handler);
    }


    /**
     * @param $path
     * @param $handler
     * @return mixed
     */
    public function patch($path, $handler)
    {
        return $this->add(['PATCH'], $path, $handler);
    }


    /**
     * @param $path
     * @param $handler
     * @return mixed
     */
    public function options($path, $handler)
    {
        return $this->add(['OPTIONS'], $path, $handler);
    }

    /**
     * @param $path
     * @param $handler
     * @return mixed
     */
    public function form($path, $handler)
    {
        return $this->add(['GET','POST'], $path, $handler);
    }

    /**
     * @param $path
     * @param $handler
     * @return mixed
     */
    public function any($path, $handler)
    {
        return $this->add(['GET','POST','PUT','DELETE','OPTIONS','PATCH'], $path, $handler);
    }
}
