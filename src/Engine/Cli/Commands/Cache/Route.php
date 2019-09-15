<?php

namespace TT\Engine\Cli\Commands\Cache;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TT\Engine\App;

class Route extends Command
{
    protected static $defaultName = 'route:cache';

    private $file;

    private $content = "";


    public function __construct()
    {
        $this->file = App::getInstance()->routesCacheFile();

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Routes files cache clear or create')
            ->setHelp('php manage route:cache [--create]');

        $this->addOption(
            'create',
            'c',
            InputOption::VALUE_NONE,
            'Route cache create'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        App::getInstance()->routing();

        if ($input->getOption('create')) {
            $routes = App::get('route')->getRoutes();
            $this->content .= "<?php return [\n\n";
            $this->create($routes);
            $this->content .= '];';
            if (file_put_contents($this->file, $this->content)) {
                $output->writeln(
                    '<fg=green>Routes cached successfully</>'
                );
            } else {
                $output->writeln(
                    '<fg=green>Routes cached error</>'
                );
            }
        } else {
            if (file_exists($this->file)) {
                unlink($this->file);
            }
            $output->writeln(
                '<fg=green>Routes cache clear successfully</>'
            );
        }
    }


    private function create($routes)
    {
        foreach ($routes as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $this->content .= "\t [\n\n";
                } else {
                    $this->content .= "\t'" . $key . "' => [\n\n";
                }

                $this->create($value);

                $this->content .= "\t],\n\n";
            } else {
                if (is_bool($value)) {
                    $value = $value ? "true" : "false";
                } elseif (!\is_int($value)) {
                    $value = "'$value'";
                }

                if (is_numeric($key)) {
                    $this->content .= "\t$value, \n\n";
                } else {
                    $this->content .= "\t'" . $key . "' => $value, \n\n";
                }
            }
        }
    }
}
