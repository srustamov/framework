<?php namespace TT\Engine\Cli;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */

use TT\Engine\App;
use TT\Facades\Str;
use TT\Engine\Cli\Route as CliRoute;
use TT\Engine\LoadEnvVariables;

class Console
{


    /**
     * @param $command
     * @param bool $shell
     * @return null
     */
    public static function command($command, $shell = false)
    {
        if ($shell === true) {
            return shell_exec($command);
        }

        if (!is_array($command)) {
            $command = explode(' ', $command);
        }

        static::run(array_merge(['manage'], array_filter($command)));
    }


    /**
     * @param array $argv
     */
    public static function run(array $argv)
    {
        $instance = new static();

        if (isset($argv[1])) {
            $manage = array_slice($argv, '1');
        } else {
            return PrintConsole::commandList();
        }

        PrintConsole::output();

        switch (strtolower($manage[0])) {
            case 'runserver':
            case 'serve':
            case 'start':
            case 'run':
                $instance->startPhpDevelopmentServer($manage);
                break;
            case 'session:table':
                CreateTables::session($manage);
                break;
            case 'users:table':
                CreateTables::users();
                break;
            case 'cache:table':
                CreateTables::cache();;
                break;
            case 'view:cache':
                $instance->clearViewCache();
                break;
            case 'config:cache':
                Config::clearConfigsCacheOrCreate($manage[1] ?? null);
                break;
            case 'route:cache':
                CliRoute::clearRoutesCacheOrCreate($manage[1] ?? null);
                break;
            case 'route:list':
                CliRoute::list();
                break;
            case 'key:generate':
                $instance->keyGenerate();
                break;
            case 'build':
            case 'prod':
            case 'production':
                $instance->getProduction();
                break;
            case 'create:controller':
            case 'create:model':
            case 'create:middleware':
            case 'create:resource':
            case 'create:facade':
            case 'c:middleware':
            case 'c:c':
            case 'c:m':
            case 'c:r':
            case 'c:f':
                Create::execute($manage);
                break;
            default:
                PrintConsole::commandList();
                break;
        }
    }

    public function startPhpDevelopmentServer($port = 8000)
    {
        $port = is_numeric($port) ? $port : 8000;
        new PrintConsole('green', "\nPhp Server Run <http://localhost:$port>\n");
        exec('php -S localhost:' . $port . ' -t ' . basename(public_path()));
    }

    public function clearViewCache()
    {
        foreach (glob(path('storage/cache/views/*')) as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    echo "Delete: [{$file}]\n";
                } else {
                    new PrintConsole('error', 'Delete failed:[' . $file . ']');
                }
            }
        }
        new PrintConsole('green', "\n\nCache files clear successfully \n\n");
    }

    public function keyGenerate()
    {
        $key = Str::random(60);
        try {
            $this->envFileChangeFragment('APP_KEY', $key);
            new PrintConsole('green', 'key:' . $key . "\n");
        } catch (\Exception $e) {
            new PrintConsole('error', $e->getMessage() . "\n");
        }
    }

    public function getProduction()
    {
        $this->appDebugFalse();
        $this->keyGenerate();
        call_user_func_array([
            new LoadEnvVariables(App::getInstance()),
            'handle'
        ], []);
        self::command('config:cache --create');
        self::command('route:cache --create');
        new PrintConsole('success', PHP_EOL . 'Getting Application in Production :)' . PHP_EOL);
    }

    protected function appDebugFalse()
    {
        try {
            $this->envFileChangeFragment('APP_DEBUG', 'FALSE');
        } catch (\Exception $e) {
            new PrintConsole('error', $e->getMessage() . "\n");
        }
    }


    protected function envFileChangeFragment($fragment, $value)
    {
        $app = App::get('app');

        $content = file_get_contents($app->envFile());

        file_put_contents(
            $app->envFile(),
            preg_replace_callback('/{$fragment}\s?+.?=.*/',
                function ($m) use ($value) {
                    return $fragment . '=' . $key;
                },
                $content
            )

        );

        if (file_exists($app->envCacheFile())) {
            unlink($app->envCacheFile());
        }

    }


}
