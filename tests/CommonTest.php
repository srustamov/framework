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
       $app = require __DIR__.'/bootstrap.php';

       $this->assertTrue($app->isBoot());

   }


   public function testRouting()
   {

       $_SERVER['REQUEST_URI'] = '/';

       $_SERVER['REQUEST_METHOD'] = 'GET';


       Route::get('/',function (Request $request){
           $this->assertTrue($request->isMethod('GET'));
           $this->assertEquals($request->url(),'/');
           return 'response';
       });

       $response = Route::run();

       $this->assertTrue($response instanceof  Response);


       $_SERVER['REQUEST_URI'] = '/test/samir';

       Route::flush();

       Route::get('/test/{author}',function (Request $request){
           $this->assertTrue($request->isMethod('GET'));
           $this->assertEquals($request->url(),'/test/samir');
           $this->assertEquals($request->params('author'),'samir');
           return 'My name is '.ucfirst($request->params('author'));
       })->pattern(['author' => '[\w]+'])->run();

       $this->assertEquals(\TT\Facades\Response::getContent(),'My name is Samir');

   }
}
