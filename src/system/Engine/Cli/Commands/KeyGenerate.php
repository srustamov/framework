<?php


namespace TT\Engine\Cli\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TT\Engine\Cli\Helper;
use TT\Engine\App;

class KeyGenerate extends Command
{
    protected static $defaultName = 'key:generate';

    protected function configure()
    {
        $this
            ->setDescription('Application security key generate')
            ->setHelp('php manage key:generate [--return]');
        $this->addOption(
            'return',
            'r',
            InputOption::VALUE_NONE,
            'generate unique key'
        );

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $key = App::get('str')->random(60);

        if($input->getOption('return')) {
            return $output->writeln(
                '<fg=yellow;options=bold>KEY: </><fg=green>'.$key.'</>'
            );
        }

        try {
            Helper::envFileChangeFragment('APP_KEY', $key);
            App::get('config')->set('app.key',$key);
            $output->writeln(
                '<fg=green>key:'.$key.'</>'
            );
        } catch (\Exception $e) {
            $output->writeln(
                '<fg=red>'.$e->getMessage().'</>'
            );
        }

    }



}