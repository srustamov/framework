<?php namespace TT\Engine\Http;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */

use Exception;
use InvalidArgumentException;
use TT\Engine\App;
use TT\Facades\File;
use UnexpectedValueException;

class Response
{
    private $content;

    /**@var Parameters*/
    private $headers = [];

    private $statusCode = 200;

    private $protocol = 'HTTP/1.1';

    private $statusMessage;

    private $charset = 'UTF-8';

    private $refresh = 0;

    private $messages = [
        100 => 'Continue' ,
        101 => 'Switching Protocols' ,
        102 => 'Processing' ,
        200 => 'OK' ,
        201 => 'Created' ,
        202 => 'Accepted' ,
        203 => 'Non-Authoritative Information' ,
        204 => 'No Content' ,
        205 => 'Reset Content' ,
        206 => 'Partial Content' ,
        207 => 'Multi-status' ,
        208 => 'Already Reported' ,
        300 => 'Multiple Choices' ,
        301 => 'Moved Permanently' ,
        302 => 'Found' ,
        303 => 'See Other' ,
        304 => 'Not Modified' ,
        305 => 'Use Proxy' ,
        306 => 'Switch Proxy' ,
        307 => 'Temporary Redirect' ,
        400 => 'Bad Request' ,
        401 => 'Unauthorized' ,
        402 => 'Payment Required' ,
        403 => 'Forbidden' ,
        404 => 'Not Found' ,
        405 => 'Method Not Allowed' ,
        406 => 'Not Acceptable' ,
        407 => 'Proxy Authentication Required' ,
        408 => 'Request Time-out' ,
        409 => 'Conflict' ,
        410 => 'Gone' ,
        411 => 'Length Required' ,
        412 => 'Precondition Failed' ,
        413 => 'Request Entity Too Large' ,
        414 => 'Request-URI Too Large' ,
        415 => 'Unsupported Media Type' ,
        416 => 'Requested range not satisfiable' ,
        417 => 'Expectation Failed' ,
        418 => 'I\'m a teapot' ,
        422 => 'Unprocessable Entity' ,
        423 => 'Locked' ,
        424 => 'Failed Dependency' ,
        425 => 'Unordered Collection' ,
        426 => 'Upgrade Required' ,
        428 => 'Precondition Required' ,
        429 => 'Too Many Requests' ,
        431 => 'Request Header Fields Too Large' ,
        451 => 'Unavailable For Legal Reasons' ,
        500 => 'Internal Server Error' ,
        501 => 'Not Implemented' ,
        502 => 'Bad Gateway' ,
        503 => 'Service Unavailable' ,
        504 => 'Gateway Time-out' ,
        505 => 'HTTP Version not supported' ,
        506 => 'Variant Also Negotiates' ,
        507 => 'Insufficient Storage' ,
        508 => 'Loop Detected' ,
        511 => 'Network Authentication Required' ,
    ];


    /**
     * Response constructor.
     * @param string $content
     * @param Int $statusCode
     * @param array $headers
     */
    public function __construct($content = '', $statusCode = 200, array $headers = [])
    {
        $this->make($content, (int) $statusCode, $headers);
    }


    /**
     * @param string $content
     * @param Int $statusCode
     * @param array $headers
     * @return $this
     */
    public function make($content, $statusCode = 200, array $headers = []): self
    {
        $this->setContent($content);

        $this->headers = new Parameters($headers);

        $this->setStatusCode((int) $statusCode);

        return $this;
    }

    /**
     * @param Int $code
     * @param null $message
     * @return $this
     */
    public function setStatusCode(Int $code, $message = null): self
    {
        if ($message === null) {
            $message = $this->messages[ $code ] ?? '';
        }

        $this->statusMessage = $message;

        $this->statusCode = $code;

        return $this;
    }

    /**
     * @param $data
     * @param null $statusCode
     * @return Response
     */
    public function json($data = null, $statusCode = null): Response
    {
        $this->contentType('application/json');

        if ($statusCode !== null && is_int($statusCode)) {
            $this->setStatusCode($statusCode);
        }

        if ($data !== null) {
            $this->setContent(json_encode($data));

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new InvalidArgumentException(json_last_error_msg());
            }
        }

        return $this;
    }


    /**
     * @param String $path
     * @param String $fileName
     * @param string $disposition
     * @return Response
     */
    public function download(String $path, String $fileName = null, $disposition = 'attachment'): self
    {
        $this->header('Content-Disposition', $disposition.';filename='.($fileName !== null ? $fileName : urlencode($fileName)));
        $this->header('Content-Type', 'application/force-download');
        $this->header('Content-Type', 'application/octet-stream');
        $this->header('Content-Type', 'application/download');
        $this->header('Content-Description', 'File Transfer');
        $this->header('Content-Length', File::size($path));
        $this->setContent(File::get($path));

        return $this;
    }

    /**
     * @param $contentType
     * @return Response
     */
    public function contentType($contentType): self
    {
        return $this->header('Content-Type', $contentType);
    }

    /**
     * @param $name
     * @param $value
     * @param bool $replace
     * @return Response
     */
    public function header($name, $value, $replace = true): self
    {
        $this->headers->set($name, ['value' => $value , 'replace' => $replace]);

        return $this;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }

        return $this;
    }

    /**
     * @param String $charset
     * @return $this
     */
    public function charset(String $charset): self
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * @param $name
     * @return bool|mixed
     */
    public function getHeader($name)
    {
        if ($this->hasHeader($name)) {
            return $this->headers->get($name);
        }

        $headers = headers_list();

        return $headers[ $name ] ?? false;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasHeader($name): Bool
    {
        return $this->headers->has($name);
    }

    /**
     * @param $name
     * @return Response
     */
    public function removeHeader($name): Response
    {
        $this->headers->remove($name);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param $content
     * @return $this
     */
    public function setContent($content): self
    {
        if ($content instanceof $this) {
            return $this;
        }

        if (is_array($content)) {
            $content = json_encode($content);
        }

        if (null !== $content && !is_string($content) && !is_numeric($content) && !is_callable(array( $content , '__toString' ))) {
            throw new UnexpectedValueException(sprintf('The Response content must be a string or object implementing __toString(), "%s" given.', gettype($content)));
        }

        if (is_object($content) && is_callable(array( $content , '__toString' ))) {
            $content = $content->__toString();
        }

        $this->content = $content;

        return $this;
    }

    /**
     * @param $content
     * @return Response
     */
    public function appendContent($content): self
    {
        return $this->setContent($this->getContent().$content);
    }

    /**
     * @param $content
     * @return Response
     */
    public function prependContent($content): Response
    {
        return $this->setContent($content.$this->getContent());
    }

    /**
     * @param Int $refresh
     * @return $this
     */
    public function refresh(Int $refresh): self
    {
        $this->refresh = $refresh;

        return $this;
    }

    /**
     * @param String $url
     * @param int $statusCode
     * @param int $refresh
     * @return mixed
     */
    public function redirect(String $url, $refresh = 0, $statusCode = 302): self
    {
        $this->header('Location', $url, true);

        $this->setStatusCode($statusCode);

        $this->refresh($refresh);

        return $this;
    }



    /**
     * @return Response
     */
    public function headersSend(): self
    {
        if (!headers_sent()) {
            foreach ($this->headers->all() as $name => $header) {
                header($name . ':' . $header[ 'value' ], $header[ 'replace' ]);
            }

            header(sprintf('%s %d %s', $this->protocol(), $this->statusCode, $this->statusMessage));
        }

        return $this;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function send()
    {
        if ($this->refresh > 0) {
            sleep($this->refresh);
        }

        if (!$this->hasHeader('Content-Type')) {
            $this->contentType("text/html;charset={$this->charset}");
        }

        if (App::get('request')->isJson()) {
            $this->contentType('application/json;charset='.$this->charset);
        }

        $this->headersSend();

        if (App::get('request')->isMethod('HEAD')) {
            $this->setContent(null);
        }

        $this->sendContent();

        $this->setContent(null);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (!CONSOLE) {
            static::closeOutputBuffers();
        }
    }


    /**
     * @author Symfony
    */
    public static function closeOutputBuffers()
    {
        $status = ob_get_status(true);

        $level = count($status);

        $flags = defined('PHP_OUTPUT_HANDLER_REMOVABLE') ? PHP_OUTPUT_HANDLER_REMOVABLE | PHP_OUTPUT_HANDLER_FLUSHABLE  : -1;

        while ($level-- > 0 && ($s = $status[$level]) && (!isset($s['del']) ? !isset($s['flags']) || $flags === ($s['flags'] & $flags) : $s['del'])) {
            ob_end_flush();
        }
    }


    /**
     * @param String $protocol
     * @return string|Response
     */
    public function protocol(String $protocol = null)
    {
        if ($protocol !== null) {
            $this->protocol = $protocol;

            return $this;
        }

        return $_SERVER[ 'SERVER_PROTOCOL' ] ?? $this->protocol;
    }

    /**
     * @return Response
     */
    public function sendContent(): Response
    {
        echo $this->content;

        return $this;
    }


    /**
     *
     * @throws Exception
     */
    public function __toString(): string
    {
        $this->send();

        return '';
    }

    /**
     * @param mixed $headers
     * @return Response
     */
    public function setHeaders($headers): self
    {
        $this->headers = $headers;
        return $this;
    }
}
