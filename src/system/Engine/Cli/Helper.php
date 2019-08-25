<?php


namespace TT\Engine\Cli;

use TT\Engine\App;

class Helper
{

    public static function envFileChangeFragment($fragment, $value)
    {
        $app = App::get('app');

        $content = file_get_contents($app->envFile());

        file_put_contents(
            $app->envFile(),
            preg_replace_callback("/{$fragment}\s?+.?=.*/",
                function ($m) use ($fragment,$value) {
                    return $fragment . '=' . $value;
                },
                $content
            )

        );

        if (file_exists($app->envCacheFile())) {
            unlink($app->envCacheFile());
        }
    }

}