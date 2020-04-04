<?php namespace TT\Engine\Http\Routing;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */



trait Methods
{
    /**
     * @param $path
     * @param $callback
     * @return mixed
     */
    public function get($path, $callback)
    {
        return $this->add(['GET'], $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return mixed
     */
    public function post($path, $callback)
    {
        return $this->add(['POST'], $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return mixed
     */
    public function put($path, $callback)
    {
        return $this->add(['PUT'], $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return mixed
     */
    public function delete($path, $callback)
    {
        return $this->add(['DELETE'], $path, $callback);
    }


    /**
     * @param $path
     * @param $callback
     * @return mixed
     */
    public function patch($path, $callback)
    {
        return $this->add(['PATCH'], $path, $callback);
    }


    /**
     * @param $path
     * @param $callback
     * @return mixed
     */
    public function options($path, $callback)
    {
        return $this->add(['OPTIONS'], $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return mixed
     */
    public function form($path, $callback)
    {
        return $this->add(['GET','POST'], $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return mixed
     */
    public function any($path, $callback)
    {
        return $this->add(['GET','POST','PUT','DELETE','OPTIONS','PATCH'], $path, $callback);
    }
}
