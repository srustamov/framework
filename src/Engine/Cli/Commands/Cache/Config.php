<?php

namespace TT\Engine\Cli\Commands\Cache;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TT\Engine\App;

class Config extends Command
{
    protected static $defaultName = 'config:cache';

    private $file;

    private $content = "";


    public function __construct()
    {
        $this->file = App::getInstance()->configsCacheFile();

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Routes files cache clear or create')
            ->setHelp('php manage route:cache [--create]');

        $this->addOption(
            'create',
            'c',
            InputOption::VALUE_NONE,
            'Route cache create'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('create')) {
            $configs = App::get('config')->all();
            $this->content .= "<?php return ";
            $this->content .= var_export($configs,true);
            $this->content .= ';';
            if (file_put_contents($this->file, $this->content)) {
                $output->writeln(
                    '<fg=green>Configs cached successfully</>'
                );
            } else {
                $output->writeln(
                    '<fg=green>Configs cached error</>'
                );
            }
        } else {
            if (file_exists($this->file)) {
                unlink($this->file);
            }
            $output->writeln(
                '<fg=green>Configs cache clear successfully</>'
            );
        }
    }
}
