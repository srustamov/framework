<?php


namespace TT\Engine\Cli\Commands\Table;



use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TT\Engine\App;

class Session extends Command
{

    protected static $defaultName = 'session:table';


    protected function configure()
    {
        $this
            ->setDescription('Migrate sessions table')
            ->setHelp('php manage session:table [--table=tableName]');

        $this->addOption(
            'table',
            '-t',
            InputOption::VALUE_REQUIRED,
            'table name'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = $input->getOption('table') ?? App::get('config')->get('session.table','sessions');
        
        try {
            App::get('database')->exec($this->getSql($table));

            $output->writeln("<fg=green>Create {$table} table successfully</>");
        } catch (\PDOException $e) {
            if ($e->getCode() === '42S01') {
                $output->writeln("<fg=red>{$table} table or view already exists</>");
            } else {
                $output->writeln("<fg=red>{$e->getmessage()}</>");
            }

        }


    }



    protected function getSql(string $table)
    {
        return sprintf('CREATE TABLE IF NOT EXISTS %s (
            `session_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
            `expires` int(100) NOT NULL,
            `data` text COLLATE utf8_unicode_ci,
             UNIQUE(`session_id`),
             PRIMARY KEY(`session_id`)
             
           )', $table);
    }
}