<?php

namespace TT\Engine\Http\Pipeline;

use Closure;
use Exception;
use TT\Engine\App;
use TT\Engine\Reflections;

class Pipeline
{

    private $pipes = [];

    private $request;

    private $app;


    public function __construct(App $app)
    {
        $this->app = $app;
    }


    public function __invoke($request)
    {
        $this->request = $request;

        return $this->app['response'];
    }

    public function send($request)
    {
        $this->request = $request;

        return $this;
    }


    public function pipe($pipe)
    {
        if (is_array($pipe)) {
            $this->pipes = array_merge($this->pipes, $pipe);
        } else {
            $this->pipes[] = $pipe;
        }

        return $this;
    }


    public function run()
    {
        foreach ($this->pipes as $pipe) {

            [$pipe,$arguments] = $this->preparePipe($pipe);

            $response  = $pipe->handle($this->request, $this->next(),...$arguments);

            if (!$this->app->isInstance($response, 'response')) {

                $this->app['response']->send();

                $this->app->end();
            }
        }

        $this->pipes = [];

        return $this->request;
    }


    public function then(Closure $callback)
    {
        return $callback($this->run());
    }


    private function preparePipe($pipe)
    {
        if (is_string($pipe)) {
            if(strpos($pipe,':')) {
                [$pipe,$arguments] = explode(':',$pipe,2);
                $arguments = explode(',',$arguments);
            }
            return [new $pipe(
                ...Reflections::methodParameters($pipe, '__construct')
            ),$arguments ?? []];
        } elseif (is_object($pipe)) {
            return [$pipe,[]];
        } else {
            throw new Exception('Object not callable');
        }
    }


    private function next()
    {
        return function ($request) {
            return $this($request);
        };
    }
}
