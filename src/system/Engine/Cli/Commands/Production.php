<?php


namespace TT\Engine\Cli\Commands;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TT\Engine\App;
use TT\Engine\Cli\Helper;


class Production extends Command
{
    protected static $defaultName = 'production';

    protected function configure()
    {
        $this
            ->setDescription('Switches the application to production mode')
            ->setHelp('php manage production');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {   
        $keyGenerate = $this->getApplication()->find('key:generate');
        $keyGenerate->run(new ArrayInput([]),$output);

        App::get('config')->set('app.debug',false);
        $config = $this->getApplication()->find('config:cache');
        $config->run(new ArrayInput(['--create'  => true,]),$output);

        $route = $this->getApplication()->find('route:cache');
        $route->run(new ArrayInput(['--create'  => true,]),$output);

        Helper::envFileChangeFragment('APP_DEBUG','FALSE');

        $output->writeln(
            "\n<fg=green;options=bold>Application in production :)</>\n"
        );
    }



}