<?php namespace TT\Engine;

use ReflectionException;
use TT\Exceptions\TTException;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class RegisterExceptionHandler
{
    private App $app;

    /**
     * RegisterExceptionHandler constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function handle(): void
    {
        if (!$this->app->runningConsole() && $this->app->isDebug()) {
            (new Run)->pushHandler(new PrettyPageHandler)->register();
        } else {
            (new TTException)->register($this->app);
        }
    }
}
