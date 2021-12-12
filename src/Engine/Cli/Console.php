<?php namespace TT\Engine\Cli;


use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class Console
{
    protected static array $commands = [
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
        Commands\StorageLink::class,
        Commands\KeyGenerate::class,
        Commands\StartServer::class,
        Commands\Production::class,
        Commands\Development::class,
    ];
    protected static ?Application $app = null;
    private static ?Console $instance = null;
    private static ?SymfonyStyle $writer = null;

    public static function setApplication(Application $app): Console|static
    {
        self::$app = $app;

        return self::getInstance();
    }

    public static function getInstance(): Console|static
    {
        if (self::$instance === null) {
            self::$instance = new static;
        }

        return self::$instance;
    }

    public static function setCommand($commands): Console|static
    {
        $commands = is_array($commands) ? $commands : [$commands];

        self::$commands = array_merge(self::$commands, $commands);

        return self::getInstance();
    }

    /**
     * @throws \Exception
     */
    public static function run(): int
    {
        $app = self::getApplication();

        foreach (self::$commands as $command) {
            if (is_string($command)) {
                $app->add(new $command());
            } else {
                $app->add($command);
            }
        }

        return $app->run();
    }

    public static function getApplication(): Application
    {
        if (self::$app === null) {
            self::$app = new Application();
        }

        return self::$app;
    }

    public static function write($message, $style = 'text')
    {
        self::getWriter()->$style($message);
    }

    public static function getWriter(): SymfonyStyle
    {
        if (!self::$writer) {
            self::$writer = new SymfonyStyle(self::getInput(), self::getOutput());
        }
        return self::$writer;
    }

    private static function getInput(): ArgvInput
    {
        return new ArgvInput();
    }

    private static function getOutput(): ConsoleOutput
    {
        return new ConsoleOutput();
    }


}
