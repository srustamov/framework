<?php namespace TT\Engine\Cli;



use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class Console
{
    protected static $commands = [
        Commands\Create\Middleware::class,
        Commands\Create\Model::class,
        Commands\Create\Controller::class,
        Commands\Cache\Route::class,
        Commands\Cache\Config::class,
        Commands\Cache\View::class,
        Commands\Table\Session::class,
        Commands\Table\User::class,
        Commands\Table\Cache::class,
        Commands\RouteList::class,
        Commands\KeyGenerate::class,
        Commands\StartServer::class,
        Commands\Production::class,
    ];

    private static $instance;

    protected static $app;

    public static function setApplication(Application $app)
    {
        self::$app = $app;

        return self::getInstance();
    }


    public static function setCommand(Command $command)
    {
        self::$commands[] = $command;

        return self::getInstance();
    }


    public static function run()
    {
        $app = self::getApplication();
        
        foreach (self::$commands as $command)
        {
            if(is_string($command)) {
                $app->add(new $command());
            } else {
                $app->add($command);
            }
        }

        return $app->run();
    }


    public static function getApplication()
    {
        if(self::$app === null){
            self::$app = new Application();
        }

        return self::$app;
    }


    public static function getInstance()
    {
        if(self::$instance === null){
            self::$instance = new static;
        }

        return self::$instance;
    }
}