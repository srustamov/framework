<?php

use PHPUnit\Framework\TestCase;
use TT\Engine\Http\Request;
use TT\Engine\Http\Response;
use TT\Facades\Route;

class CommonTest extends TestCase
{


    public function testBoot()
    {
        /**@var $app TT\Engine\App*/
        global $app;

        $this->assertTrue($app->isBoot());
    }


    public function testRouting()
    {

        $_SERVER['REQUEST_URI'] = '/';

        Route::get('/', function (Request $request) {
            $this->assertTrue($request->isMethod('GET'));
            $this->assertEquals($request->url(), '/');
            return 'response';
        });

        /**@var $response TT\Engine\Http\Response*/
        $response = Route::run();

        $this->assertTrue($response instanceof  Response);

        $response->make('');

        $_SERVER['REQUEST_URI'] = '/test/123';

        Route::get('/test/{id}', function ($id, Request $request) {
            $this->assertEquals($id, '123');
            $this->assertTrue($request->isMethod('GET'));
            $this->assertEquals($request->url(), '/test/123');
            $this->assertEquals($request->routeParams('id'), '123');
            return 'Id is ' . $id;
        })->pattern(['id' => '[1-9]([0-9]+)?']);
        
        /**@var $response TT\Engine\Http\Response*/
        $response = Route::run();

        $this->assertEquals($response->getContent(), 'Id is 123');
    }



    public function testRequest()
    {
        $_SERVER['REQUEST_URI'] = '/request/test';

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST = [
            'id' => 123,
            'email' => 'rustemovv96@gmail.com',
            'name' => 'Samir'
        ];

        app('request')->prepare()->method = 'POST';


        Route::post('/request/test', function (Request $request) {
            $this->assertEquals($request->id, 123);
            $this->assertEquals($request->email, 'rustemovv96@gmail.com');
            $this->assertEquals($request->name, 'Samir');
            $this->assertEquals($request->all(), $_POST);

            $request->map(function ($value, $key) {
                if ($key === 'name') {
                    return 'Samir Rustamov';
                }
                return $value;
            });

            $this->assertEquals($request->name, 'Samir Rustamov');

            $request->filter(function ($value, $key) {
                return $key === 'name';
            });

            $this->assertEquals($request->all(), ['name' => 'Samir Rustamov']);

            return '';
        });

        Route::run();
    }
}
