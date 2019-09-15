<?php


namespace TT\Engine\Cli\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StorageLink extends Command
{
    protected static $defaultName = 'storage:link';

    protected function configure()
    {
        $this
            ->setDescription('To create the symbolic link')
            ->setHelp('Symbolic link from '. public_path('storage').' to '. storage_path('public'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        exec(
            'ln -s ' . storage_path('public') . ' ' . public_path('storage')
        );
        $output->writeln(
            '<fg=green;options=bold>Symbolic link created</>'
        );
    }
}
