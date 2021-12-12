<?php

use TT\Engine\App;
use TT\Engine\Http\Request;
use TT\Facades\Route;

require __DIR__.'/vendor/autoload.php';

$app = new App(dirname(__DIR__));



Route::get('/',function (Request $request) {
    return $request->all();
});

/** @noinspection PhpUnhandledExceptionInspection */
$response = $app->bootstrap()->routing();


/** @noinspection PhpUnhandledExceptionInspection */
$response->send();