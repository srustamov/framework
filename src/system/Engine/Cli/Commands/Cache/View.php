<?php

namespace TT\Engine\Cli\Commands\Cache;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;



class View extends Command
{
    protected static $defaultName = 'view:cache';

    protected function configure()
    {
        $this
            ->setDescription('View files cache clear')
            ->setHelp('php manage view:cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        foreach (glob(storage_path('cache/views/*')) as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    $output->writeln(
                        sprintf('<fg=green>Delete:[%s]</>',$file)
                    );
                    echo "Delete: [{$file}]\n";
                } else {
                    $output->writeln(
                        sprintf('<fg=red>Delete failed:[%s]</>',$file)
                    );
                }
            }
        }
        
        $output->writeln(
            '<fg=green>Cache files clear successfully</>'
        );
        

    }



}