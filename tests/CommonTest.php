<?php

use PHPUnit\Framework\TestCase;
use TT\Facades\Route;
use TT\Engine\Http\Request;
use TT\Engine\Http\Response;


class CommonTest extends TestCase
{
    
    public function testBoot()
    {
        /**@var $app TT\Engine\App*/
        $app = require __DIR__ . '/bootstrap.php';

        $this->assertTrue($app->isBoot());
    }


    public function testRouting()
    {

        $_SERVER['REQUEST_URI'] = '/';

        $_SERVER['REQUEST_METHOD'] = 'GET';


        Route::get('/', function (Request $request) {
            $this->assertTrue($request->isMethod('GET'));
            $this->assertEquals($request->url(), '/');
            return 'response';
        });

        $response = Route::run();

        $this->assertTrue($response instanceof  Response);

        $_SERVER['REQUEST_URI'] = '/test/123';

        Route::flush();

        Route::get('/test/{id}', function ($id, Request $request) {
            $this->assertEquals($id, '123');
            $this->assertTrue($request->isMethod('GET'));
            $this->assertEquals($request->url(), '/test/123');
            $this->assertEquals($request->params('id'), '123');
            return 'Id is ' . $id;
        })->pattern(['id' => '[1-9]([0-9]+)?'])->run();

        $this->assertEquals(\TT\Facades\Response::getContent(), 'Id is 123');
    }



    public function testRequest()
    {

        Route::flush();

        $_SERVER['REQUEST_URI'] = '/';

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST['id'] = 123;

        $_POST['email'] = 'rustemovv96@gmail.com';

        $_POST['name'] = 'Samir';

        app('request')->prepare()->method = 'POST';

        Route::post('/',function(Request $request){
            
            $this->assertEquals($request->id,123);
            $this->assertEquals($request->email, 'rustemovv96@gmail.com');
            $this->assertEquals($request->name, 'Samir');
            $this->assertEquals($request->all(), $_POST);

            $request->map(function($value,$key){
                if($key === 'name') {
                    return 'Samir Rustamov';
                }
                return $value;
            });

            $this->assertEquals($request->name, 'Samir Rustamov');

            $request->filter(function ($value, $key) {
                return $key === 'name';
            });

            $this->assertEquals($request->all(), ['name' => 'Samir Rustamov']);

        })->run();

    }
}
