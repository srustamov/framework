<?php


namespace TT\Engine\Cli\Commands\Table;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TT\Engine\App;

class User extends Command
{
    protected static $defaultName = 'users:table';


    protected function configure()
    {
        $this
            ->setDescription('Migrate users table')
            ->setHelp('php manage users:table [--table=tableName]');

        $this->addOption(
            'table',
            '-t',
            InputOption::VALUE_REQUIRED,
            'table name'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = $input->getOption('table') ?? 'users';
        
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
        return sprintf('CREATE TABLE IF NOT EXISTS `%s` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                        `password` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                        `email` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                        `status` tinyint(1) NOT NULL DEFAULT \'1\',
                        `remember_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                        `forgotten_pass_code` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
                        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `email` (`email`),
                        UNIQUE KEY `remember_token` (`remember_token`)
                )', $table);
    }
}
