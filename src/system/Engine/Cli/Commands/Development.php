<?php


namespace TT\Engine\Cli\Commands;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TT\Engine\Cli\Helper;
use TT\Engine\App;


class Development extends Command
{
    protected static $defaultName = 'development';

    protected function configure()
    {
        $this
            ->setDescription('Switches the application to development mode')
            ->setHelp('php manage development');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        App::get('config')->set('app.debug',true);
        $config = $this->getApplication()->find('config:cache');
        $config->run(new ArrayInput([]),$output);

        $route = $this->getApplication()->find('route:cache');
        $route->run(new ArrayInput([]),$output);

        Helper::envFileChangeFragment('APP_DEBUG','TRUE');

        $output->writeln(
            "\n<fg=green;options=bold>Application in development!</>\n"
        );
    }


}