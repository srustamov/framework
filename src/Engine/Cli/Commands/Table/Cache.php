<?php


namespace TT\Engine\Cli\Commands\Table;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TT\Engine\App;

class Cache extends Command
{
    protected static $defaultName = 'cache:table';


    protected function configure()
    {
        $this
            ->setDescription('Migrate cache table')
            ->setHelp('php manage cache:table [--table=tableName]');

        $this->addOption(
            'table',
            '-t',
            InputOption::VALUE_REQUIRED,
            'table name'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = $input->getOption('table') ??
                 App::get('config')->get('cache.database.table', 'cache');
        
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

        return 1;
    }



    protected function getSql(string $table): string
    {
        return sprintf("CREATE TABLE IF NOT EXISTS `%s` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `cache_key` varchar(255) NOT NULL,
                    `cache_value` longtext NOT NULL,
                    `expires` int(20) NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `cache_key` (`cache_key`)
                    ) DEFAULT CHARSET=utf8
                ", $table);
    }
}
