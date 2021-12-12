<?php


namespace TT\Engine\Cli\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use TT\Engine\App;

class RouteList extends Command
{
    protected static $defaultName = 'route:list';

    private $content;

    protected function configure()
    {
        $this
            ->setDescription('Show routes')
            ->setHelp('php manage route:list');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        App::getInstance()->routing();

        $routes = App::get('route')->getRoutes();

        $table = new Table($output);

        $table->setStyle('box');

        $table->setHeaders(['Method', 'Url', 'Callback','Middleware','Pattern','Name','Namespace']);

        $rows = [];
        
        foreach ($routes as $method => $parameters) {
            if ($method === 'NAMES') {
                continue;
            }
            foreach ($parameters as $param) {
                $rows[] = [
                   $method,
                   $param['prefix'],
                   is_callable($param['callback']) ? 'Closure' : $param['callback'],
                   implode(',', $param['middleware']),
                   $this->showPattern($param['patterns']),
                   $param['name'],
                   $param['namespace'],
                ];
                $rows[] = new TableSeparator();
            }
        }

        $table->setRows($rows);

        $table->render();

        return 1;
    }


    private function showPattern($pattern)
    {
        $return  = '';

        if (empty($pattern)) {
            return $return;
        }

        foreach ($pattern as $key => $value) {
            $return .= $key.':'.$value."\n";
        }

        return $return;
    }
}
