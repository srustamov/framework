<?php


namespace TT\Engine\Cli\Commands\Create;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Controller extends Command
{
    protected static $defaultName = 'create:controller';

    private $namespace = 'App\Controllers';

    private $directory = 'Controllers';


    protected function configure()
    {
        $this
            ->setDescription('Create Controller class')
            ->setHelp('php manage create:controller FooController');

        $this->addArgument(
            'name',
            InputArgument::REQUIRED,
            'Controller name'
        );

        $this->addOption(
            'resource',
            'r',
            InputOption::VALUE_NONE,
            'create resource controller'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resource = $input->getOption('resource');

        $name = $path = trim($input->getArgument('name'), '/');

        $file = app_path($this->directory.'/'.$path.'.php');

        if (file_exists($file)) {
            return $output->writeln('<fg=red>The Controller was already created</>');
        }

        if (strpos($path, '/') !== false) {
            $part = explode('/', $path);

            $name = array_pop($part);

            $this->namespace .= '\\'.implode('\\', $part);

            $this->directory .= '/'.implode('/', $part);
        }

        if (!is_dir(app_path($this->directory))) {
            if (!mkdir(app_path($this->directory), 0755, true)) {
                return $output->writeln(
                    sprintf(
                        '<fg=red>Directory "%s" was not created</>',
                        app_path($this->directory)
                    )
                );
            }
        }

        if (!touch($file)) {
            return $output->writeln(
                sprintf(
                    '<fg=red>\'File "%s" was not created\'</>',
                    app_path($file)
                )
            );
        }

        if ($resource) {
            $content = file_get_contents(
                dirname(__DIR__,2).'/resource/resource.mask'
            );
        } else {
            $content = file_get_contents(
                dirname(__DIR__,2).'/resource/controller.mask'
            );
        }

        $content = str_replace(
            [':namespace',':name'],
            ['namespace '.$this->namespace,$name],
            $content
        );
        if (!file_put_contents($file, $content)) {
            return $output->writeln(
                sprintf(
                    '<fg=red>Create Controller file[%s] failed</>',
                    app_path($file)
                )
            );
        }

        $output->writeln(
            sprintf('<fg=green>Create %s successfully</>', $name)
        );
    }
}
