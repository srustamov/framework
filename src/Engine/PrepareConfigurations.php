<?php namespace TT\Engine;

class PrepareConfigurations
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function handle(): void
    {
        $configurations = [];

        $cache_file = $this->app->configsCacheFile();

        if (file_exists($cache_file)) {
            $configurations = require $cache_file;
        } else {
            foreach (glob($this->app->configsPath('*')) as $file) {
                $configurations[pathinfo($file, PATHINFO_FILENAME)] = require $file;
            }
        }

        $this->app->singleton('config', new Config($configurations));
    }
}
