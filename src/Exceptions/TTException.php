<?php namespace TT\Exceptions;

use Exception;
use RuntimeException;
use TT\Facades\Config;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */
class TTException extends Exception
{

    /**
     * @param $e
     * @throws Exception
     */
    private function show($e): void
    {
        /**@var $e Exception */

        $this->writeErrorLog($e);

        if (CONSOLE) {
            $error = "
*--------------ERROR-----------------
* Message| {$e->getMessage()}
*           
*--------*---------------------------
* File:  | {$e->getFile()}
*--------*---------------------------
* Line:  | {$e->getLine()}
*--------*---------------------------
";

            exit("\e[0;31m".$error."\e[0m\n");
        }

        if (Config::get('app.debug',false)) {
            require __DIR__.'/resource/exception.php';
        } else {
            abort(500);
        }
    }


    /**
     * @param $e
     * @throws Exception
     */
    public function handler($e): void
    {
        $this->show($e);
    }


    /**
     * @param $e
     */
    public function writeErrorLog($e)
    {
        /**@var $e Exception */
        $file = $e->getFile();

        $line = $e->getLine();

        $message = $e->getMessage();

        $date = date('Y-m-d H:m:s');

        $path = path('storage/logs/errors/');

        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException($path . ' not found');
        }

        $log_file = rtrim($path, '/') . '/' . date('Y-m-d') . '.log';

        if (!file_exists($log_file)) {
            touch($log_file);

            chmod($log_file, 0755);
        }

        $logData = "[{$date}] File:{$file} |Message:{$message} |Line:{$line}\n";

        @file_put_contents($log_file, $logData, FILE_APPEND);
    }


    /**
     * @param $level
     * @param $message
     * @param string $file
     * @param int $line
     * @throws Exception
     */
    public function handleError($level, $message, $file = '', $line = 0): void
    {
        if (error_reporting() & $level) {
            $this->show($this->createFakeExceptionObject(array(
                'file' => $file,
                'message' => $message,
                'line' => $line,
                'code' => $level
            )));
        }
    }


    /**
     * @param $data
     * @return object
     */
    public function createFakeExceptionObject($data)
    {
        $e = new class
        {
            private $data;

            public function setExceptionData($data): void
            {
                $this->data = $data;
            }

            public function getFile()
            {
                return $this->data['file'];
            }

            public function getMessage()
            {
                return $this->data['message'];
            }

            public function getLine()
            {
                return $this->data['line'];
            }

            public function getCode()
            {
                return $this->data['code'];
            }
        };

        $e->setExceptionData($data);

        return $e;
    }


    /**
     * @throws Exception
     */
    public function handleShutdown(): void
    {
        if ((($error = error_get_last()) !== null) &&
            in_array($error['type'], [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE], true))
        {
            $this->show($this->createFakeExceptionObject(array(
                'file' => $error['file'] ?? '',
                'message' => 'Fatal Error: ' . $error['message'] ?? '',
                'line' => $error['line'] ?? '',
                'code' => 0,
            )));
        }
    }


    public function register(): void
    {
        ini_set('display_errors', 'Off');

        error_reporting(-1);

        set_error_handler([$this, 'handleError']);

        set_exception_handler([$this, 'handler']);

        register_shutdown_function([$this, 'handleShutdown']);

    }
}
