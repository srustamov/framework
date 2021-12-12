<?php


namespace TT\Engine\Cli\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartServer extends Command
{
    protected static $defaultName = 'server:start';

    protected function configure()
    {
        $this
            ->setDescription('Start php development server')
            ->setHelp('php manage serve [--port=8000]');

        $this->addOption(
            'port',
            'p',
            InputOption::VALUE_REQUIRED,
            'server port[default 8000]'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $port = $input->getOption('port') ?: 8000;

        $output->writeln(
            '<fg=green;options=bold><server run <http://localhost:'.$port.'></>'
        );

        exec('php -S localhost:'.$port.' -t public');


    }
}
