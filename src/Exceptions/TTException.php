<?php namespace TT\Exceptions;

use Exception;
use Throwable;
use TT\Engine\App;
use TT\Engine\Cli\Console;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */
class TTException extends Exception
{
    private App $app;

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
     * @throws Exception
     */
    private function show($e): void
    {
        /**@var $e Exception */

        $this->writeErrorLog($e);

        if (CONSOLE) {
            Console::getWriter()->table([['message', 'file', 'line']], [
                [$e->getMessage(), $e->getFile(), $e->getLine(),]
            ]);
            exit;
        }

        if ($this->app->isDebug() && file_exists(__DIR__ . '/resource/exception.php')) {
            require __DIR__ . '/resource/exception.php';
        } else {
            abort(500, $this->app->isDebug()  ? $e : 'Server error');
        }
    }

    /**
     * @param $e
     */
    public function writeErrorLog($e)
    {
        try {
            /**@var $e Exception */
            $file = $e->getFile();

            $line = $e->getLine();

            $message = $e->getMessage();

            $date = date('Y-m-d H:m:s');

            $path = path('storage/logs/errors/');


            if (!is_dir($path)) {
                if (!@mkdir($path, 0755, true)) {
                    return;
                }
            }

            $log_file = rtrim($path, '/') . '/' . date('Y-m-d') . '.log';

            if (!file_exists($log_file)) {

                touch($log_file);

                chmod($log_file, 0755);
            }


            $logData = "[$date] File:$file |Message:$message |Line:$line\n";


            @file_put_contents($log_file, $logData, FILE_APPEND);

        } catch (Throwable) {
        }
    }


    /**
     * @param $level
     * @param $message
     * @param string $file
     * @param int $line
     * @throws Exception
     */
    public function handleError($level, $message, string $file = '', int $line = 0): void
    {
        if (error_reporting() & $level) {
            $this->show($this->createException(array(
                'file'    => $file,
                'message' => $message,
                'line'    => $line,
                'code'    => $level
            )));
        }
    }


    /**
     * @param $data
     * @return object
     */
    public function createException($data): object
    {
        $this->message = $data['message'];
        $this->code = $data['code'];
        $this->line = $data['line'];
        $this->file = $data['file'];

        return $this;
    }


    /**
     * @throws Exception
     */
    public function handleShutdown(): void
    {
        if ((($error = error_get_last()) !== null) &&
            in_array($error['type'], [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE], true)) {
            $this->show($this->createException(['code' => $error['type'],...$error]));
        }
    }


    public function register(App $app): void
    {

        $this->app = $app;

        ini_set('display_errors', 'Off');

        error_reporting(-1);

        set_error_handler([$this, 'handleError']);

        set_exception_handler([$this, 'handler']);

        register_shutdown_function([$this, 'handleShutdown']);

    }
}
