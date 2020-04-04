<?php

if(file_exists(__DIR__.'/../vendor/autoload')) {
    require __DIR__.'/../vendor/autoload';
} else{
    if(file_exists(__DIR__ . '/../../../autoload.php')) {
        require __DIR__ . '/../../../autoload.php';
    }
}


$app = new TT\Engine\App(dirname(__DIR__));

$app->bootstrap();

TT\Facades\Config::set('app', [
    'debug' => true,
    'key' => 'your_secret_key',
    'url' => 'http://localhost:8000'
]);
